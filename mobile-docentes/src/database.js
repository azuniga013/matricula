import * as SQLite from 'expo-sqlite';

const db = SQLite.openDatabaseSync('docentes.db');

export function initDatabase() {
  db.execSync(`
    PRAGMA journal_mode = WAL;
    CREATE TABLE IF NOT EXISTS ofertas (id INTEGER PRIMARY KEY, datos TEXT NOT NULL, actualizado_en TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS alumnos (oferta_id INTEGER NOT NULL, matricula_id INTEGER PRIMARY KEY, datos TEXT NOT NULL, actualizado_en TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS calificaciones (oferta_id INTEGER NOT NULL, estudiante_id INTEGER NOT NULL, datos TEXT NOT NULL, actualizado_en TEXT NOT NULL, PRIMARY KEY(oferta_id, estudiante_id));
    CREATE TABLE IF NOT EXISTS cola_sincronizacion (uuid TEXT PRIMARY KEY, tipo TEXT NOT NULL, oferta_id INTEGER NOT NULL, datos TEXT NOT NULL, creado_en TEXT NOT NULL, reintentos INTEGER NOT NULL DEFAULT 0, ultimo_error TEXT);
  `);
}

const now = () => new Date().toISOString();
const parse = (rows) => rows.map((row) => JSON.parse(row.datos));

export function replaceOffers(items) {
  db.withTransactionSync(() => {
    db.execSync('DELETE FROM ofertas');
    items.forEach((item) => db.runSync('INSERT INTO ofertas (id, datos, actualizado_en) VALUES (?, ?, ?)', [item.id, JSON.stringify(item), now()]));
  });
}
export function cachedOffers() { return parse(db.getAllSync('SELECT datos FROM ofertas ORDER BY id DESC')); }

export function replaceStudents(offerId, items) {
  db.withTransactionSync(() => {
    db.runSync('DELETE FROM alumnos WHERE oferta_id = ?', [offerId]);
    items.forEach((item) => db.runSync('INSERT INTO alumnos (oferta_id, matricula_id, datos, actualizado_en) VALUES (?, ?, ?, ?)', [offerId, item.matricula_id, JSON.stringify(item), now()]));
  });
}
export function cachedStudents(offerId) { return parse(db.getAllSync('SELECT datos FROM alumnos WHERE oferta_id = ? ORDER BY matricula_id', [offerId])); }

export function replaceGrades(offerId, items) {
  db.withTransactionSync(() => {
    db.runSync('DELETE FROM calificaciones WHERE oferta_id = ?', [offerId]);
    items.forEach((item) => db.runSync('INSERT INTO calificaciones (oferta_id, estudiante_id, datos, actualizado_en) VALUES (?, ?, ?, ?)', [offerId, item.estudiante_id, JSON.stringify(item), now()]));
  });
}
export function cachedGrades(offerId) { return parse(db.getAllSync('SELECT datos FROM calificaciones WHERE oferta_id = ?', [offerId])); }

export function queue(type, offerId, data) {
  const uuid = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
  db.runSync('INSERT INTO cola_sincronizacion (uuid, tipo, oferta_id, datos, creado_en) VALUES (?, ?, ?, ?, ?)', [uuid, type, offerId, JSON.stringify(data), now()]);
}
export function pending() { return db.getAllSync('SELECT * FROM cola_sincronizacion ORDER BY creado_en'); }
export function removePending(uuid) { db.runSync('DELETE FROM cola_sincronizacion WHERE uuid = ?', [uuid]); }
export function markError(uuid, error) { db.runSync('UPDATE cola_sincronizacion SET reintentos = reintentos + 1, ultimo_error = ? WHERE uuid = ?', [String(error).slice(0, 500), uuid]); }
export function clearLocalData() { db.execSync('DELETE FROM ofertas; DELETE FROM alumnos; DELETE FROM calificaciones; DELETE FROM cola_sincronizacion;'); }
