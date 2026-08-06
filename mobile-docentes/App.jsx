import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Alert, Button, FlatList, SafeAreaView, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import NetInfo from '@react-native-community/netinfo';
import { currentUser, grades, login, logout, offers, saveAttendance, saveGrades, students } from './src/api';
import { cachedGrades, cachedOffers, cachedStudents, clearLocalData, initDatabase, markError, pending, queue, removePending, replaceGrades, replaceOffers, replaceStudents } from './src/database';

const today = () => new Date().toISOString().slice(0, 10);
const fullName = (student) => `${student.nombre || ''} ${student.apellido || ''}`.trim();
const MODULES = [
  { id: 'ofertas', title: 'Ofertas Académicas', detail: 'Lista de estudiantes por oferta' },
  { id: 'asistencia', title: 'Asistencia Diaria', detail: 'Lista y pase de asistencia' },
  { id: 'calificaciones', title: 'Calificaciones', detail: 'Lista y notas de estudiantes' },
];

export default function App() {
  const [user, setUser] = useState(null);
  const [online, setOnline] = useState(true);
  const [loading, setLoading] = useState(true);
  const [databaseReady, setDatabaseReady] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [items, setItems] = useState([]);
  const [module, setModule] = useState(null);
  const [periodId, setPeriodId] = useState(null);
  const [selected, setSelected] = useState(null);
  const [studentsForOffer, setStudentsForOffer] = useState([]);
  const [gradeRows, setGradeRows] = useState({});
  const [attendance, setAttendance] = useState({});
  const [date, setDate] = useState(today());
  const [syncing, setSyncing] = useState(false);

  useEffect(() => {
    initDatabase();
    setItems(cachedOffers());
    setDatabaseReady(true);
    NetInfo.fetch().then((state) => setOnline(Boolean(state.isConnected)));
    const unsubscribe = NetInfo.addEventListener((state) => setOnline(Boolean(state.isConnected)));
    currentUser().then((profile) => {
      if (!profile.docente_id) throw new Error('Esta cuenta no está vinculada a un docente');
      setUser(profile);
    }).catch(() => {}).finally(() => setLoading(false));
    return unsubscribe;
  }, []);

  const pendingCount = useMemo(() => databaseReady ? pending().length : 0, [databaseReady, items, selected, attendance, gradeRows, syncing]);
  const periods = useMemo(() => Object.values(items.reduce((all, offer) => {
    const period = offer.periodo_academico;
    if (period?.id) all[period.id] = period;
    return all;
  }, {})).sort((a, b) => String(b.nombre).localeCompare(String(a.nombre))), [items]);
  const visibleOffers = useMemo(() => periodId ? items.filter((item) => String(item.periodo_academico_id || item.periodo_academico?.id) === String(periodId)) : items, [items, periodId]);

  async function refresh(filterPeriod = periodId) {
    if (!online) return Alert.alert('Sin conexión', 'Se muestran los datos descargados anteriormente.');
    setSyncing(true);
    try {
      const downloadedOffers = await offers(filterPeriod);
      if (filterPeriod) {
        const retained = cachedOffers().filter((item) => String(item.periodo_academico_id || item.periodo_academico?.id) !== String(filterPeriod));
        replaceOffers([...retained, ...downloadedOffers]);
      } else replaceOffers(downloadedOffers);
      for (const offer of downloadedOffers) {
        const [enrolled, gradeData] = await Promise.all([students(offer.id), grades(offer.id)]);
        replaceStudents(offer.id, enrolled);
        replaceGrades(offer.id, gradeData);
      }
      setItems(cachedOffers());
      if (selected) await openOffer(selected.id);
    } catch (error) { Alert.alert('No se pudo sincronizar', error.message); }
    finally { setSyncing(false); }
  }

  async function synchronizeQueue() {
    if (!online) return { procesadas: 0, pendientes: pending().length, error: 'Sin conexión a internet.' };
    setSyncing(true);
    let procesadas = 0;
    let errorSincronizacion = null;
    try {
      for (const operation of pending()) {
        const data = JSON.parse(operation.datos);
        try {
          if (operation.tipo === 'asistencia') await saveAttendance(operation.oferta_id, data.fecha, data.asistencias);
          if (operation.tipo === 'calificaciones') await saveGrades(operation.oferta_id, data.calificaciones);
          removePending(operation.uuid);
          procesadas++;
        } catch (error) {
          markError(operation.uuid, error.message);
          errorSincronizacion = error.message || 'El servidor no pudo guardar la información.';
          if ([401, 403, 422].includes(error.status)) break;
        }
      }
      await refresh();
      return { procesadas, pendientes: pending().length, error: errorSincronizacion };
    } finally { setSyncing(false); }
  }

  async function openOffer(offerId) {
    const offer = items.find((item) => item.id === offerId) || cachedOffers().find((item) => item.id === offerId);
    if (!offer) return;
    setSelected(offer);
    const enrolled = cachedStudents(offerId);
    const gradeData = cachedGrades(offerId);
    setStudentsForOffer(enrolled);
    setGradeRows(Object.fromEntries(gradeData.map((row) => [row.estudiante_id, { nota_final: row.nota_final ?? '', faltas: row.faltas ?? 0, observaciones: row.observaciones ?? '' }])));
    setAttendance(Object.fromEntries(enrolled.map((row) => [row.matricula_id, { estado: 'presente', observacion: '' }])));
    if (online) {
      try {
        const [freshStudents, freshGrades] = await Promise.all([students(offerId), grades(offerId)]);
        replaceStudents(offerId, freshStudents); replaceGrades(offerId, freshGrades);
        setStudentsForOffer(freshStudents);
        setGradeRows(Object.fromEntries(freshGrades.map((row) => [row.estudiante_id, { nota_final: row.nota_final ?? '', faltas: row.faltas ?? 0, observaciones: row.observaciones ?? '' }])));
      } catch (_) { /* conservar copia offline */ }
    }
  }

  async function saveAttendanceLocally() {
    const asistencias = studentsForOffer.map((student) => ({ matricula_id: student.matricula_id, estado: attendance[student.matricula_id]?.estado || 'presente', observacion: attendance[student.matricula_id]?.observacion || null }));
    queue('asistencia', selected.id, { fecha: date, asistencias });
    if (!online) return Alert.alert('Guardado local', 'La asistencia queda pendiente de sincronización hasta recuperar internet.');
    const resultado = await synchronizeQueue();
    if (resultado.error) return Alert.alert('Asistencia pendiente', `${resultado.error} Se conserva localmente para reintentar.`);
    Alert.alert('Asistencia sincronizada', `${resultado.procesadas} operación(es) guardada(s) en el servidor.`);
  }
  async function saveGradesLocally() {
    const calificaciones = studentsForOffer.map((student) => {
      const id = student.estudiante_id || student.id;
      return { estudiante_id: id, nota_final: gradeRows[id]?.nota_final || null, faltas: Number(gradeRows[id]?.faltas || 0), observaciones: gradeRows[id]?.observaciones || null };
    });
    queue('calificaciones', selected.id, { calificaciones });
    if (!online) return Alert.alert('Guardado local', 'Las calificaciones quedan pendientes de sincronización hasta recuperar internet.');
    const resultado = await synchronizeQueue();
    if (resultado.error) return Alert.alert('Calificaciones pendientes', `${resultado.error} Se conservan localmente para reintentar.`);
    Alert.alert('Calificaciones sincronizadas', `${resultado.procesadas} operación(es) guardada(s) en el servidor.`);
  }
  async function submitLogin() { setLoading(true); try { const profile = await login(email.trim(), password); setUser(profile); await refresh(null); } catch (error) { Alert.alert('No se pudo iniciar sesión', error.message); } finally { setLoading(false); } }
  async function closeSession() { await logout(); clearLocalData(); setUser(null); setModule(null); setSelected(null); setItems([]); }

  if (loading) return <Centered text="Preparando aplicación docente..." />;
  if (!user) return <Login {...{ email, setEmail, password, setPassword, submit: submitLogin }} />;
  if (selected) return <StudentList module={module} offer={selected} students={studentsForOffer} attendance={attendance} setAttendance={setAttendance} grades={gradeRows} setGrades={setGradeRows} date={date} setDate={setDate} online={online} saveAttendance={saveAttendanceLocally} saveGrades={saveGradesLocally} back={() => setSelected(null)} />;
  if (!module) return <Menu user={user} online={online} pendingCount={pendingCount} modules={MODULES} open={setModule} sync={() => refresh(null)} syncing={syncing} logout={closeSession} />;
  return <OfferList module={MODULES.find((item) => item.id === module)} offers={visibleOffers} periods={periods} periodId={periodId} selectPeriod={(id) => { setPeriodId(id); if (online) refresh(id); }} open={openOffer} back={() => setModule(null)} sync={() => refresh(periodId)} syncing={syncing} />;
}

function Menu({ user, online, pendingCount, modules, open, sync, syncing, logout }) { return <SafeAreaView style={styles.container}><View style={styles.header}><View><Text style={styles.title}>Portal Docente</Text><Text style={styles.sub}>{user.nombre} · {online ? 'En línea' : 'Modo offline'} · {pendingCount} pendientes</Text></View><Button title={syncing ? 'Sincronizando' : 'Sincronizar'} disabled={syncing} onPress={sync} /></View><ScrollView contentContainerStyle={styles.list}>{modules.map((item) => <TouchableOpacity key={item.id} style={styles.menuCard} onPress={() => open(item.id)}><Text style={styles.cardTitle}>{item.title}</Text><Text style={styles.muted}>{item.detail}</Text></TouchableOpacity>)}</ScrollView><View style={styles.footer}><Button title="Cerrar sesión" onPress={logout} /></View></SafeAreaView>; }
function PeriodFilter({ periods, periodId, selectPeriod }) { return <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}><TouchableOpacity style={[styles.chip, !periodId && styles.chipOn]} onPress={() => selectPeriod(null)}><Text>Todos</Text></TouchableOpacity>{periods.map((period) => <TouchableOpacity key={period.id} style={[styles.chip, String(periodId) === String(period.id) && styles.chipOn]} onPress={() => selectPeriod(period.id)}><Text>{period.codigo || period.nombre}</Text></TouchableOpacity>)}</ScrollView>; }
function OfferList({ module, offers, periods, periodId, selectPeriod, open, back, sync, syncing }) { return <SafeAreaView style={styles.container}><View style={styles.header}><Button title="Menú" onPress={back} /><View style={styles.headerGrow}><Text style={styles.title}>{module.title}</Text><Text style={styles.sub}>Seleccione período y oferta</Text></View><Button title={syncing ? '...' : 'Actualizar'} disabled={syncing} onPress={sync} /></View><PeriodFilter periods={periods} periodId={periodId} selectPeriod={selectPeriod} /><FlatList data={offers} keyExtractor={(item) => String(item.id)} contentContainerStyle={styles.list} ListEmptyComponent={<Text style={styles.muted}>No hay ofertas para el período seleccionado.</Text>} renderItem={({ item }) => <TouchableOpacity style={styles.card} onPress={() => open(item.id)}><Text style={styles.cardTitle}>{item.codigo} · {item.nivel_academico?.nombre || 'Oferta'}</Text><Text style={styles.muted}>{item.periodo_academico?.nombre} · {item.horario?.nombre || 'Sin horario'}</Text></TouchableOpacity>} /></SafeAreaView>; }
function StudentList({ module, offer, students, attendance, setAttendance, grades, setGrades, date, setDate, online, saveAttendance, saveGrades, back }) { const isAttendance = module === 'asistencia'; const isGrades = module === 'calificaciones'; return <SafeAreaView style={styles.container}><View style={styles.header}><Button title="Ofertas" onPress={back} /><View style={styles.headerGrow}><Text style={styles.title}>{isAttendance ? 'Asistencia Diaria' : isGrades ? 'Calificaciones' : 'Estudiantes'}</Text><Text style={styles.sub}>{offer.codigo} · {offer.periodo_academico?.nombre}</Text></View></View><ScrollView contentContainerStyle={styles.list}>{isAttendance && <><Text style={styles.section}>Fecha</Text><TextInput style={styles.input} value={date} onChangeText={setDate} placeholder="YYYY-MM-DD" /></>}{students.length === 0 && <Text style={styles.muted}>No hay estudiantes descargados para esta oferta.</Text>}{students.map((student) => { const id = student.estudiante_id || student.id; const row = grades[id] || {}; return <View key={student.matricula_id} style={styles.card}><Text style={styles.cardTitle}>{student.codigo} · {fullName(student)}</Text>{isAttendance && <View style={styles.actions}>{['presente', 'falta', 'justificada', 'tardanza'].map((state) => <TouchableOpacity key={state} style={[styles.pill, attendance[student.matricula_id]?.estado === state && styles.pillOn]} onPress={() => setAttendance({ ...attendance, [student.matricula_id]: { ...(attendance[student.matricula_id] || {}), estado: state } })}><Text>{state}</Text></TouchableOpacity>)}</View>}{isGrades && <><TextInput style={styles.input} placeholder="Nota final" keyboardType="decimal-pad" value={String(row.nota_final ?? '')} onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, nota_final: value } })} /><TextInput style={styles.input} placeholder="Faltas" keyboardType="number-pad" value={String(row.faltas ?? 0)} onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, faltas: value } })} /></>}</View>; })}{isAttendance && <Button title={online ? 'Guardar y sincronizar' : 'Guardar asistencia local'} onPress={saveAttendance} />}{isGrades && <Button title={online ? 'Guardar y sincronizar' : 'Guardar notas localmente'} onPress={saveGrades} />}</ScrollView></SafeAreaView>; }
function Login({ email, setEmail, password, setPassword, submit }) { return <SafeAreaView style={styles.center}><Text style={styles.title}>Cursos SVP · Docentes</Text><Text style={styles.muted}>Use su usuario administrativo vinculado como docente.</Text><TextInput style={styles.input} placeholder="Correo" autoCapitalize="none" keyboardType="email-address" value={email} onChangeText={setEmail} /><TextInput style={styles.input} placeholder="Contraseña" secureTextEntry value={password} onChangeText={setPassword} /><Button title="Iniciar sesión" onPress={submit} /></SafeAreaView>; }
function Centered({ text }) { return <View style={styles.center}><ActivityIndicator size="large" /><Text style={styles.muted}>{text}</Text></View>; }
const styles = StyleSheet.create({ container: { flex: 1, backgroundColor: '#f8fafc' }, center: { flex: 1, justifyContent: 'center', padding: 24, gap: 14 }, header: { padding: 18, backgroundColor: '#fff', flexDirection: 'row', alignItems: 'center', gap: 12 }, headerGrow: { flex: 1 }, title: { fontSize: 21, fontWeight: '700', color: '#1e3a8a' }, sub: { fontSize: 12, color: '#64748b' }, list: { padding: 16, gap: 12 }, card: { backgroundColor: '#fff', padding: 15, borderRadius: 10, borderWidth: 1, borderColor: '#e2e8f0', gap: 8 }, menuCard: { backgroundColor: '#eff6ff', padding: 20, borderRadius: 12, borderWidth: 1, borderColor: '#bfdbfe', gap: 6 }, cardTitle: { fontWeight: '700', color: '#111827', fontSize: 16 }, muted: { color: '#64748b', marginTop: 6 }, input: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 8, padding: 11, marginTop: 9 }, section: { fontSize: 16, fontWeight: '700', marginTop: 8, color: '#1f2937' }, actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 }, pill: { paddingHorizontal: 8, paddingVertical: 5, borderRadius: 14, backgroundColor: '#e2e8f0' }, pillOn: { backgroundColor: '#86efac' }, footer: { padding: 16 }, filters: { paddingHorizontal: 12, paddingVertical: 10, gap: 8 }, chip: { paddingHorizontal: 12, paddingVertical: 8, backgroundColor: '#e2e8f0', borderRadius: 16 }, chipOn: { backgroundColor: '#93c5fd' } });
