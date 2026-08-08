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

export async function currentUser() { return request('/me'); }
export async function offers(periodId = null) {
  const query = periodId ? `?periodo_academico_id=${encodeURIComponent(periodId)}` : '';
  return request(`/asistencias/ofertas-disponibles${query}`);
}
export async function students(offerId) { return request(`/asistencias/estudiantes-por-oferta?oferta_academica_id=${offerId}`); }

export async function grades(offerId) {
  const page = await request(`/calificaciones?oferta_academica_id=${offerId}&per_page=200`);
  return page.data || [];
}

export async function saveAttendance(offerId, date, attendances) {
  return request('/asistencias/registrar', {
    method: 'POST',
    body: JSON.stringify({ oferta_academica_id: offerId, fecha: date, asistencias: attendances }),
  });
}

export async function attendanceForOffer(offerId, date) {
  return request(`/asistencias/por-oferta?oferta_academica_id=${offerId}&fecha=${encodeURIComponent(date)}`) || [];
}

export async function saveGrades(offerId, calificaciones) {
  return request('/calificaciones/registrar', {
    method: 'POST',
    body: JSON.stringify({ oferta_academica_id: offerId, calificaciones }),
  });
}
