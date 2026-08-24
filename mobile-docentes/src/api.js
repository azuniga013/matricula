import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';

const API_URL = Constants.expoConfig?.extra?.apiUrl || process.env.EXPO_PUBLIC_API_URL;
const TOKEN_KEY = 'docente_auth_token';

async function request(path, options = {}) {
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
  });
  const body = await response.json().catch(() => ({}));
  if (!response.ok || body.resultado === 'R') {
    const error = new Error(body.mensaje || 'No fue posible comunicarse con el servidor');
    error.status = response.status;
    error.body = body;
    throw error;
  }
  return body.data;
}

export async function login(email, password) {
  const response = await fetch(`${API_URL}/login`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });
  const body = await response.json().catch(() => ({}));
  if (!response.ok || body.resultado !== 'A') throw new Error(body.mensaje || 'Credenciales inválidas');
  if (!body.data?.usuario?.docente_id) throw new Error('Esta cuenta no está vinculada a un docente');
  await SecureStore.setItemAsync(TOKEN_KEY, body.data.token);
  return body.data.usuario;
}

export async function logout() {
  try { await request('/logout', { method: 'POST', body: '{}' }); } catch (_) { /* cierre local */ }
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}

const CREDENTIALS_KEY = 'docente_auth_credentials';

export async function hasToken() { return Boolean(await SecureStore.getItemAsync(TOKEN_KEY)); }

export async function saveBiometricCredentials(email, password) {
  await SecureStore.setItemAsync(CREDENTIALS_KEY, JSON.stringify({ email, password }));
}
export async function loadBiometricCredentials() {
  const raw = await SecureStore.getItemAsync(CREDENTIALS_KEY);
  if (!raw) return null;
  try { return JSON.parse(raw); } catch (_) { return null; }
}
export async function clearBiometricCredentials() {
  await SecureStore.deleteItemAsync(CREDENTIALS_KEY);
}

export async function currentUser() { return request('/me'); }
export async function offers(periodId = null) {
  const data = await request('/docente-movil/sincronizar');
  const ofertas = (data.ofertas || []).filter((item) => String(item.periodo_academico?.estado || item.periodo_estado || 'activo') !== 'inactivo');
  return periodId
    ? ofertas.filter((item) => String(item.periodo_academico_id || item.periodo_academico?.id) === String(periodId))
    : ofertas;
}
export async function students(offerId) {
  const data = await request(`/docente-movil/ofertas/${offerId}`);
  return data.alumnos || [];
}

export async function grades(offerId) {
  const data = await request(`/docente-movil/ofertas/${offerId}`);
  return data.calificaciones || [];
}

export async function updateWhatsappPeriodLink(offerId, whatsapp_link_periodo) {
  return request(`/docente-movil/ofertas/${offerId}/whatsapp-periodo`, {
    method: 'POST',
    body: JSON.stringify({ whatsapp_link_periodo }),
  });
}

export async function saveAttendance(uuid, offerId, date, attendance) {
  return request('/docente-movil/sincronizar', {
    method: 'POST',
    body: JSON.stringify({
      operaciones: [{
        uuid,
        tipo: 'asistencia',
        oferta_academica_id: offerId,
        fecha: date,
        datos: attendance,
      }],
    }),
  });
}

export async function attendanceForOffer(offerId, date) {
  const data = await request(`/docente-movil/ofertas/${offerId}`);
  return (data.asistencias || []).filter((item) => item.fecha === date);
}

export async function saveGrades(uuid, offerId, calificacion) {
  return request('/docente-movil/sincronizar', {
    method: 'POST',
    body: JSON.stringify({
      operaciones: [{
        uuid,
        tipo: 'calificacion',
        oferta_academica_id: offerId,
        datos: calificacion,
      }],
    }),
  });
}
