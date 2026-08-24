import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Alert, Button, FlatList, KeyboardAvoidingView, Platform, SafeAreaView, ScrollView, StyleSheet, Switch, Text, TextInput, TouchableOpacity, View } from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import * as LocalAuthentication from 'expo-local-authentication';
import NetInfo from '@react-native-community/netinfo';
import { Ionicons } from '@expo/vector-icons';
import { attendanceForOffer, clearBiometricCredentials, currentUser, grades, loadBiometricCredentials, login, logout, offers, saveAttendance, saveBiometricCredentials, saveGrades, students, updateWhatsappPeriodLink } from './src/api';
import { cachedAttendance, cachedGrades, cachedOffers, cachedStudents, clearLocalData, initDatabase, markError, pending, queue, removePending, replaceAttendance, replaceGrades, replaceOffers, replaceStudents } from './src/database';

const today = () => new Date().toISOString().slice(0, 10);
const toDateInput = (value) => { const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(value || ''); if (!m) return new Date(); return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])); };
const fromDateInput = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const fullName = (student) => `${student.nombre || ''} ${student.apellido || ''}`.trim();
const MODULES = [
  { id: 'ofertas', title: 'Ofertas Académicas', detail: 'Lista de estudiantes por oferta' },
  { id: 'asistencia', title: 'Asistencia Diaria', detail: 'Lista y pase de asistencia' },
  { id: 'calificaciones', title: 'Calificaciones', detail: 'Lista y notas de estudiantes' },
  { id: 'sincronizacion', title: 'Sincronización', detail: 'Pendientes, errores y reintentos' },
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
  const [syncSummary, setSyncSummary] = useState(null);
  const [pendingVersion, setPendingVersion] = useState(0);
  const [bioAvailable, setBioAvailable] = useState(false);
  const [bioGuardado, setBioGuardado] = useState(false);
  const [bioLoading, setBioLoading] = useState(false);

  useEffect(() => {
    (async () => {
      const [hardware, enrolled] = await Promise.all([LocalAuthentication.hasHardwareAsync(), LocalAuthentication.isEnrolledAsync()]);
      setBioAvailable(Boolean(hardware && enrolled));
      setBioGuardado(Boolean(await loadBiometricCredentials()));
    })().catch(() => {});
  }, []);

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

  const pendingCount = useMemo(() => databaseReady ? pending().length : 0, [databaseReady, items, selected, attendance, gradeRows, syncing, pendingVersion]);
  const pendingRows = useMemo(() => databaseReady ? pending() : [], [databaseReady, items, selected, attendance, gradeRows, syncing, pendingVersion]);
  const recentErrors = useMemo(() => pendingRows.filter((item) => item.ultimo_error).slice(0, 3), [pendingRows]);
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
      setSyncSummary({ type: 'ok', message: 'Datos descargados correctamente.' });
    } catch (error) { Alert.alert('No se pudo sincronizar', error.message); setSyncSummary({ type: 'error', message: error.message }); }
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
            if (operation.tipo === 'asistencia') await saveAttendance(operation.uuid, operation.oferta_id, data.fecha, data);
            if (operation.tipo === 'calificaciones') await saveGrades(operation.uuid, operation.oferta_id, data);
            removePending(operation.uuid);
            setPendingVersion((value) => value + 1);
            procesadas++;
        } catch (error) {
          markError(operation.uuid, error.message);
          errorSincronizacion = error.message || 'El servidor no pudo guardar la información.';
          if (error.status === 401) {
            await logout();
            setUser(null); setModule(null); setSelected(null); setItems([]);
            errorSincronizacion = 'La sesión expiró. Vuelva a iniciar sesión para continuar.';
            break;
          }
          if (error.status === 422) { errorSincronizacion = "El servidor rechazó el guardado (respuesta local pendiente). Revisa la oferta; si no tiene estudiantes, descárgala con internet."; break; }
          if (error.status === 403) break;
        }
      }
      await refresh();
      const resultado = { procesadas, pendientes: pending().length, error: errorSincronizacion };
      setSyncSummary(
        errorSincronizacion
          ? { type: 'warning', message: errorSincronizacion }
          : { type: 'ok', message: `${procesadas} operación(es) sincronizadas. ${resultado.pendientes} pendiente(s).` }
      );
      return resultado;
    } finally { setSyncing(false); }
  }

  async function openOffer(offerId) {
    const offer = items.find((item) => item.id === offerId) || cachedOffers().find((item) => item.id === offerId);
    if (!offer) return;
    setSelected(offer);
    const enrolled = cachedStudents(offerId);
    const gradeData = cachedGrades(offerId);
    setStudentsForOffer(enrolled);
    setGradeRows(Object.fromEntries(gradeData.map((row) => [row.estudiante_id, { nota_final: row.nota_final ?? '', faltas: row.faltas ?? 0, observaciones: row.observaciones ?? '' }])))
    const priorAttendance = cachedAttendance(offerId, date);
    setAttendance(buildAttendanceState(enrolled, priorAttendance));
    if (online) {
      try {
        const [freshStudents, freshGrades, freshAttendance] = await Promise.all([students(offerId), grades(offerId), attendanceForOffer(offerId, date)]);
        replaceStudents(offerId, freshStudents); replaceGrades(offerId, freshGrades); replaceAttendance(offerId, date, freshAttendance);
        setStudentsForOffer(freshStudents);
        setGradeRows(Object.fromEntries(freshGrades.map((row) => [row.estudiante_id, { nota_final: row.nota_final ?? '', faltas: row.faltas ?? 0, observaciones: row.observaciones ?? '' }])))
        setAttendance(buildAttendanceState(freshStudents, freshAttendance));
      } catch (_) { /* conservar copia offline */ }
    }
  }

  async function changeAttendanceDate(newDate) {
    setDate(newDate);
    if (!selected) return;
    const priorAttendance = cachedAttendance(selected.id, newDate);
    setAttendance(buildAttendanceState(studentsForOffer, priorAttendance));
    if (!online) return;
    try {
      const freshAttendance = await attendanceForOffer(selected.id, newDate);
      replaceAttendance(selected.id, newDate, freshAttendance);
      setAttendance(buildAttendanceState(studentsForOffer, freshAttendance));
    } catch (_) { /* conservar copia local */ }
  }

  async function saveAttendanceLocally() {
    if (!studentsForOffer.length) return Alert.alert('Sin estudiantes', 'Esta oferta no tiene estudiantes matriculados para registrar.');
    const asistencias = studentsForOffer.map((student) => ({
      matricula_id: student.matricula_id,
      estado: attendance[student.matricula_id]?.estado || 'presente',
      cuenta_como_falta: ['falta'].includes(attendance[student.matricula_id]?.estado || 'presente'),
      observacion: attendance[student.matricula_id]?.observacion || null,
    }));
    replaceAttendance(selected.id, date, asistencias);
    asistencias.forEach((item) => queue('asistencia', selected.id, { fecha: date, ...item }));
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
    calificaciones.forEach((item) => queue('calificaciones', selected.id, item));
    if (!online) return Alert.alert('Guardado local', 'Las calificaciones quedan pendientes de sincronización hasta recuperar internet.');
    const resultado = await synchronizeQueue();
    if (resultado.error) return Alert.alert('Calificaciones pendientes', `${resultado.error} Se conservan localmente para reintentar.`);
    Alert.alert('Calificaciones sincronizadas', `${resultado.procesadas} operación(es) guardada(s) en el servidor.`);
  }
  async function submitLogin() { setLoading(true); try { const profile = await login(email.trim(), password); if (bioGuardado) { await saveBiometricCredentials(email.trim(), password); } else { await clearBiometricCredentials(); } setUser(profile); await refresh(null); } catch (error) { Alert.alert('No se pudo iniciar sesión', error.message); } finally { setLoading(false); } }
  async function bioLogin() {
    if (!bioGuardado) return Alert.alert('Sin acceso biométrico', 'Active "Acceso con huella" y luego inicie sesión una vez para guardar el acceso.');
    setBioLoading(true);
    try {
      const result = await LocalAuthentication.authenticateAsync({ promptMessage: 'Verifique su identidad para entrar al Portal Docente', cancelLabel: 'Cancelar', disableDeviceFallback: false });
      if (!result || !result.success) return;
      const creds = await loadBiometricCredentials();
      if (!creds) return;
      const profile = await login(creds.email, creds.password);
      setUser(profile);
      await refresh(null);
    } catch (error) {
      Alert.alert('No se pudo entrar con huella', error.message || 'Inténtelo de nuevo.');
    } finally { setBioLoading(false); }
  }
  async function closeSession() { await logout(); clearLocalData(); setUser(null); setModule(null); setSelected(null); setItems([]); }

  if (loading) return <Centered text="Preparando aplicación docente..." />;
  if (!user) return <Login {...{ email, setEmail, password, setPassword, submit: submitLogin, bioAvailable, bioGuardado, setBioGuardado, bioLoading, bioLogin }} />;
  if (selected) return <StudentList module={module} offer={selected} students={studentsForOffer} attendance={attendance} setAttendance={setAttendance} grades={gradeRows} setGrades={setGradeRows} date={date} setDate={setDate} online={online} saveAttendance={saveAttendanceLocally} saveGrades={saveGradesLocally} changeDate={changeAttendanceDate} back={() => setSelected(null)} />;
  if (!module) return <Menu user={user} online={online} pendingCount={pendingCount} recentErrors={recentErrors} syncSummary={syncSummary} modules={MODULES} open={setModule} sync={() => refresh(null)} syncing={syncing} logout={closeSession} />;
  if (module === 'sincronizacion') return <SyncQueueScreen pendingRows={pendingRows} online={online} syncing={syncing} syncSummary={syncSummary} synchronize={synchronizeQueue} back={() => setModule(null)} />;
  return <OfferList module={MODULES.find((item) => item.id === module)} offers={visibleOffers} periods={periods} periodId={periodId} selectPeriod={(id) => { setPeriodId(id); if (online) refresh(id); }} open={openOffer} back={() => setModule(null)} sync={() => refresh(periodId)} syncing={syncing} />;
}

function Menu({ user, online, pendingCount, recentErrors, syncSummary, modules, open, sync, syncing, logout }) { return <SafeAreaView style={styles.container}><View style={styles.header}><View><Text style={styles.title}>Portal Docente</Text><Text style={styles.sub}>{user.nombre} · {online ? 'En línea' : 'Modo offline'} · {pendingCount} pendientes</Text></View><Button title={syncing ? 'Sincronizando' : 'Sincronizar'} disabled={syncing} onPress={sync} /></View><ScrollView contentContainerStyle={styles.list}>{syncSummary ? <View style={styles.card}><Text style={styles.cardTitle}>Estado de sincronización</Text><Text style={styles.muted}>{syncSummary.message}</Text></View> : null}{recentErrors.length ? <View style={styles.card}><Text style={styles.cardTitle}>Pendientes con error</Text>{recentErrors.map((item) => <Text key={item.uuid} style={styles.muted}>• {item.tipo}: {item.ultimo_error}</Text>)}</View> : null}{modules.map((item) => <TouchableOpacity key={item.id} style={styles.menuCard} onPress={() => open(item.id)}><Text style={styles.cardTitle}>{item.title}</Text><Text style={styles.muted}>{item.detail}</Text></TouchableOpacity>)}</ScrollView><View style={styles.footer}><Button title="Cerrar sesión" onPress={logout} /></View></SafeAreaView>; }
function SyncQueueScreen({ pendingRows, online, syncing, syncSummary, synchronize, back }) {
  async function retryNow() {
    const resultado = await synchronize();
    if (resultado.error) return Alert.alert('Sincronización incompleta', resultado.error);
    Alert.alert('Sincronización completada', `${resultado.procesadas} operación(es) aplicadas.`);
  }

  function discardPending(item) {
    Alert.alert(
      'Descartar pendiente',
      'Esta operación se eliminará del dispositivo y no volverá a sincronizarse. ¿Desea continuar?',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Descartar',
          style: 'destructive',
          onPress: () => {
            removePending(item.uuid);
            setPendingVersion((value) => value + 1);
            setSyncSummary({ type: 'warning', message: 'Operación descartada localmente.' });
          },
        },
      ]
    );
  }

  return <SafeAreaView style={styles.container}><View style={styles.header}><Button title="Menú" onPress={back} /><View style={styles.headerGrow}><Text style={styles.title}>Sincronización</Text><Text style={styles.sub}>{online ? 'Con conexión disponible' : 'Sin conexión'} · {pendingRows.length} pendiente(s)</Text></View><Button title={syncing ? '...' : 'Reintentar'} disabled={syncing || !pendingRows.length} onPress={retryNow} /></View><ScrollView contentContainerStyle={styles.list}>{syncSummary ? <View style={styles.card}><Text style={styles.cardTitle}>Último resultado</Text><Text style={styles.muted}>{syncSummary.message}</Text></View> : null}{!pendingRows.length ? <View style={styles.card}><Text style={styles.cardTitle}>Sin pendientes</Text><Text style={styles.muted}>No hay operaciones locales esperando sincronización.</Text></View> : null}{pendingRows.map((item) => <View key={item.uuid} style={styles.card}><Text style={styles.cardTitle}>{item.tipo === 'asistencia' ? 'Asistencia' : 'Calificación'}</Text><Text style={styles.muted}>Resumen: {summarizePendingPayload(item)}</Text><Text style={styles.muted}>UUID: {item.uuid}</Text><Text style={styles.muted}>Oferta: {item.oferta_id}</Text><Text style={styles.muted}>Creado: {item.creado_en}</Text><Text style={styles.muted}>Reintentos: {item.reintentos}</Text><Text style={styles.muted}>Estado: {item.ultimo_error ? (String(item.ultimo_error).toLowerCase().includes('conflicto') ? 'Conflicto' : 'Con error') : 'Pendiente'}</Text>{item.ultimo_error ? <Text style={styles.muted}>Detalle: {item.ultimo_error}</Text> : null}<View style={styles.pendingActions}><Button title="Descartar" color="#b91c1c" onPress={() => discardPending(item)} /></View></View>)}</ScrollView></SafeAreaView>;
}
function PeriodFilter({ periods, periodId, selectPeriod }) { return <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}><TouchableOpacity style={[styles.chip, !periodId && styles.chipOn]} onPress={() => selectPeriod(null)}><Text>Todos</Text></TouchableOpacity>{periods.map((period) => <TouchableOpacity key={period.id} style={[styles.chip, String(periodId) === String(period.id) && styles.chipOn]} onPress={() => selectPeriod(period.id)}><Text>{period.codigo || period.nombre}</Text></TouchableOpacity>)}</ScrollView>; }
function OfferList({ module, offers, periods, periodId, selectPeriod, open, back, sync, syncing }) {
  const [links, setLinks] = useState({});
  const [savingId, setSavingId] = useState(null);

  useEffect(() => {
    setLinks(Object.fromEntries(offers.map((offer) => [offer.id, offer.whatsapp_link_periodo || ''])));
  }, [offers]);

  async function saveWhatsappLink(offer) {
    setSavingId(offer.id);
    try {
      const updated = await updateWhatsappPeriodLink(offer.id, links[offer.id] || null);
      offer.whatsapp_link_periodo = updated?.whatsapp_link_periodo || null;
      setLinks((prev) => ({ ...prev, [offer.id]: offer.whatsapp_link_periodo || '' }));
      Alert.alert('WhatsApp', 'Link del período guardado correctamente.');
    } catch (error) {
      Alert.alert('No se pudo guardar', error.message || 'Inténtelo de nuevo.');
    } finally {
      setSavingId(null);
    }
  }

  return <SafeAreaView style={styles.container}><View style={styles.header}><Button title="Menú" onPress={back} /><View style={styles.headerGrow}><Text style={styles.title}>{module.title}</Text><Text style={styles.sub}>Seleccione período y oferta</Text></View><Button title={syncing ? '...' : 'Actualizar'} disabled={syncing} onPress={sync} /></View><PeriodFilter periods={periods} periodId={periodId} selectPeriod={selectPeriod} /><FlatList data={offers} keyExtractor={(item) => String(item.id)} contentContainerStyle={styles.list} ListEmptyComponent={<Text style={styles.muted}>No hay ofertas para el período seleccionado.</Text>} renderItem={({ item }) => <View style={styles.card}><TouchableOpacity onPress={() => open(item.id)}><Text style={styles.cardTitle}>{item.codigo} · {item.nivel_academico?.nombre || 'Oferta'}</Text><Text style={styles.muted}>{item.periodo_academico?.nombre} · {item.horario?.nombre || 'Sin horario'}</Text></TouchableOpacity>{module?.id === 'ofertas' ? <View style={styles.whatsappBox}><Text style={styles.fieldLabel}>Grupo WhatsApp</Text><Text style={styles.muted}>{item.whatsapp_grupo_nombre || 'Sin nombre configurado'}</Text><Text style={styles.fieldLabel}>Link WhatsApp del período</Text><TextInput style={styles.input} placeholder="https://chat.whatsapp.com/..." value={links[item.id] || ''} onChangeText={(value) => setLinks((prev) => ({ ...prev, [item.id]: value }))} autoCapitalize="none" autoCorrect={false} /><Button title={savingId === item.id ? 'Guardando...' : 'Guardar link'} disabled={savingId === item.id || !item.whatsapp_grupo_nombre} onPress={() => saveWhatsappLink(item)} />{!item.whatsapp_grupo_nombre ? <Text style={styles.muted}>La oferta no tiene nombre de grupo configurado.</Text> : null}</View> : null}</View>} /></SafeAreaView>;
}
const ATT_STATES = [
  { value: 'presente', label: 'Presente', note: 'Asistió normalmente', color: '#16a34a' },
  { value: 'falta', label: 'Falta', note: 'No asistió', color: '#dc2626' },
  { value: 'justificada', label: 'Justificada', note: 'Ausencia con justificación', color: '#d97706' },
  { value: 'tardanza', label: 'Tardanza', note: 'Llegó tarde', color: '#2563eb' },
];
const estadoInfo = (value) => ATT_STATES.find((item) => item.value === value) || ATT_STATES[0];
const buildAttendanceState = (studentsRows, saved = []) => {
  const savedByMatricula = Object.fromEntries(saved.map((row) => [row.matricula_id, row]));
  return Object.fromEntries(studentsRows.map((row) => {
    const savedRow = savedByMatricula[row.matricula_id];
    return [row.matricula_id, { estado: savedRow?.estado || 'presente', observacion: savedRow?.observacion || '' }];
  }));
};
const estadoBadge = (value) => { const item = estadoInfo(value); return { label: item.label, badge: { backgroundColor: item.color + '1a', color: item.color, borderColor: item.color } }; };
const summarizePendingPayload = (operation) => {
  try {
    const data = JSON.parse(operation.datos || '{}');
    if (operation.tipo === 'asistencia') {
      return [
        data.fecha ? `Fecha: ${data.fecha}` : null,
        data.matricula_id ? `Matrícula: ${data.matricula_id}` : null,
        data.estado ? `Estado: ${data.estado}` : null,
      ].filter(Boolean).join(' · ');
    }

    if (operation.tipo === 'calificaciones') {
      return [
        data.estudiante_id ? `Estudiante: ${data.estudiante_id}` : null,
        data.nota_final !== undefined && data.nota_final !== null ? `Nota: ${data.nota_final}` : 'Nota: sin asignar',
        data.faltas !== undefined ? `Faltas: ${data.faltas}` : null,
      ].filter(Boolean).join(' · ');
    }
  } catch (_) {
    return 'No se pudo resumir el contenido local.';
  }

  return 'Operación pendiente';
};

function StudentList({ module, offer, students, attendance, setAttendance, grades, setGrades, date, setDate, online, saveAttendance, saveGrades, changeDate, back }) {
  const isAttendance = module === 'asistencia';
  const isGrades = module === 'calificaciones';
  const [marking, setMarking] = useState(null);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [refreshingDate, setRefreshingDate] = useState(false);

  if (isAttendance && marking) {
    return <MarkingStudent offer={offer} student={marking} attendance={attendance} setAttendance={setAttendance} back={() => setMarking(null)} />;
  }

  async function pickDate(event, picked) {
    if (Platform.OS === 'android') setShowDatePicker(false);
    if (!event || event.type === 'dismissed' || !picked) return;
    const newDate = fromDateInput(picked);
    if (newDate === date) return;
    setDate(newDate);
    if (changeDate) {
      setRefreshingDate(true);
      try { await changeDate(newDate); } finally { setRefreshingDate(false); }
    }
  }

  return <SafeAreaView style={styles.container}><View style={styles.header}><Button title="Ofertas" onPress={back} /><View style={styles.headerGrow}><Text style={styles.title}>{isAttendance ? 'Asistencia Diaria' : isGrades ? 'Calificaciones' : 'Estudiantes'}</Text><Text style={styles.sub}>{offer.codigo} · {offer.periodo_academico?.nombre}</Text></View></View><ScrollView contentContainerStyle={styles.list}>{isAttendance && <><Text style={styles.section}>Fecha</Text><View style={styles.dateRow}><TextInput style={[styles.input, styles.dateInput]} value={date} onChangeText={setDate} placeholder="YYYY-MM-DD" autoCorrect={false} /><TouchableOpacity style={styles.calendarBtn} onPress={() => setShowDatePicker(true)} activeOpacity={0.8}><Text style={styles.calendarBtnText}>{refreshingDate ? '⏳' : '📅'}</Text></TouchableOpacity></View>{showDatePicker && <DateTimePicker value={toDateInput(date)} mode="date" display={Platform.OS === 'ios' ? 'spinner' : 'default'} onChange={pickDate} />}</>}{students.length === 0 && <Text style={styles.muted}>No hay estudiantes descargados para esta oferta.</Text>}{students.map((student) => {
    const id = student.estudiante_id || student.id;
    const row = grades[id] || {};
    if (isAttendance) {
      const selected = attendance[student.matricula_id];
      return <TouchableOpacity key={student.matricula_id} style={styles.card} onPress={() => setMarking(student)}>
        <View style={styles.studentRow}><View style={styles.studentRowGrow}><Text style={styles.cardTitle}>{fullName(student)}</Text><Text style={styles.muted}>{student.codigo}</Text></View><Text style={[styles.badge, estadoBadge(selected?.estado).badge]}>{estadoBadge(selected?.estado).label}</Text></View>
      </TouchableOpacity>;
    }
    return <View key={student.matricula_id} style={styles.card}><Text style={styles.cardTitle}>{student.codigo} · {fullName(student)}</Text><TextInput style={styles.input} placeholder="Nota final" keyboardType="decimal-pad" value={String(row.nota_final ?? '')} onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, nota_final: value } })} /><TextInput style={styles.input} placeholder="Faltas" keyboardType="number-pad" value={String(row.faltas ?? 0)} onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, faltas: value } })} /></View>;
  })}{isAttendance && <Button title={online ? 'Guardar y sincronizar' : 'Guardar asistencia local'} onPress={saveAttendance} />}{isGrades && <Button title={online ? 'Guardar y sincronizar' : 'Guardar notas localmente'} onPress={saveGrades} />}</ScrollView></SafeAreaView>;
}

function MarkingStudent({ offer, student, attendance, setAttendance, back }) {
  const matriculaId = student.matricula_id;
  const current = attendance[matriculaId] || { estado: 'presente', observacion: '' };
  const setEstado = (estado) => setAttendance({ ...attendance, [matriculaId]: { ...current, estado } });
  return <SafeAreaView style={styles.container}><View style={styles.header}><Button title="Volver" onPress={back} /><View style={styles.headerGrow}><Text style={styles.title}>{fullName(student)}</Text><Text style={styles.sub}>{student.codigo} · {offer.codigo}</Text></View></View><ScrollView contentContainerStyle={styles.list}><Text style={styles.section}>Marcar asistencia</Text>{ATT_STATES.map((state) => <TouchableOpacity key={state.value} style={[styles.flag, current.estado === state.value && { borderColor: state.color, backgroundColor: state.color + '14' }]} onPress={() => setEstado(state.value)}><View style={styles.studentRow}><View style={styles.studentRowGrow}><Text style={styles.flagLabel}>{state.label}</Text><Text style={styles.muted}>{state.note}</Text></View><View style={[styles.flagCircle, { borderColor: state.color }, current.estado === state.value && { backgroundColor: state.color }]}>{current.estado === state.value && <Text style={[styles.flagCheck, { color: '#fff' }]}>✓</Text>}</View></View></TouchableOpacity>)}<Text style={styles.muted}>Seleccione el estado y regrese para continuar. Luego presione Guardar y sincronizar al final de la lista.</Text><Button title="Guardar estado" onPress={() => { setEstado(current.estado); back(); }} /></ScrollView></SafeAreaView>;
}
function Login({ email, setEmail, password, setPassword, submit, bioAvailable, bioGuardado, setBioGuardado, bioLoading, bioLogin }) {
  const [showPassword, setShowPassword] = useState(false);
  return <KeyboardAvoidingView style={styles.loginWrap} behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={40}><ScrollView contentContainerStyle={styles.loginScroll} keyboardShouldPersistTaps="handled">
    <View style={styles.loginCard}>
      <View style={styles.logo}>{'\u{1F393}'}</View>
      <Text style={styles.loginTitle}>Cursos SVP</Text>
      <Text style={styles.loginSubtitle}>Portal Docente</Text>
      <Text style={styles.loginHint}>Ingrese con su cuenta administrativa vinculada como docente.</Text>
      <View style={styles.fieldBlock}><Text style={styles.fieldLabel}>Correo</Text><TextInput style={styles.input} placeholder="correo@dominio.com" placeholderTextColor="#94a3b8" autoCapitalize="none" autoCorrect={false} keyboardType="email-address" value={email} onChangeText={setEmail} /></View>
      <View style={styles.fieldBlock}><Text style={styles.fieldLabel}>Contraseña</Text><View style={styles.passwordRow}><TextInput style={[styles.input, styles.passwordInput]} placeholder="••••••••" placeholderTextColor="#94a3b8" secureTextEntry={!showPassword} value={password} onChangeText={setPassword} onSubmitEditing={submit} returnKeyType="go" autoCorrect={false} autoCapitalize="none" /><TouchableOpacity style={styles.eyeBtn} onPress={() => setShowPassword((v) => !v)} activeOpacity={0.7}><Text style={styles.eyeText}>{showPassword ? '\u{1F441}\uFE0F' : '\u{1F441}'}</Text><Text style={styles.eyeLabel}>{showPassword ? 'Ocultar' : 'Ver'}</Text></TouchableOpacity></View>{showPassword && password.length > 0 ? <Text style={styles.passwordHint}>Se muestra lo que escribió; puede ocultar con "Ocultar".</Text> : null}</View>
      <TouchableOpacity style={[styles.primaryBtn, (!email || !password) && styles.primaryBtnDisabled]} onPress={submit} disabled={!email || password.length === 0} activeOpacity={0.85}>
        <Text style={styles.primaryBtnText}>Iniciar sesión</Text>
      </TouchableOpacity>
      {bioAvailable ? <View style={styles.sessionBox}><View style={styles.bioRow}><View style={styles.bioRowGrow}><Text style={styles.fieldLabel}>Acceso con huella</Text><Text style={styles.muted}>{bioGuardado ? 'Guardado en este dispositivo. Podrá entrar sin escribir la contraseña.' : 'Guarde su cuenta en este dispositivo para entrar con un toque.'}</Text></View><Switch value={bioGuardado} onValueChange={(v) => { setBioGuardado(v); if (!v) clearBiometricCredentials().catch(() => {}); Alert.alert(v ? 'Acceso biométrico activado' : 'Acceso biométrico desactivado', v ? 'Al iniciar sesión se guardará su cuenta para entrar con huella o rostro.' : 'Ya no se guardará su cuenta para entrar con huella.'); }} trackColor={{ false: '#cbd5e1', true: '#bfdbfe' }} thumbColor={bioGuardado ? '#1e3a8a' : '#f1f5f9'} /></View></View> : null}
      {bioAvailable && bioGuardado ? <TouchableOpacity style={styles.bioBtn} onPress={bioLogin} disabled={bioLoading} activeOpacity={0.85}>
        <Text style={styles.bioBtnText}>{bioLoading ? 'Verificando huella…' : 'Entrar con huella o rostro'}</Text>
      </TouchableOpacity> : null}
    </View>
  </ScrollView></KeyboardAvoidingView>;
}
function Centered({ text }) { return <View style={styles.center}><ActivityIndicator size="large" /><Text style={styles.muted}>{text}</Text></View>; }
const styles = StyleSheet.create({ container: { flex: 1, backgroundColor: '#f8fafc' }, center: { flex: 1, justifyContent: 'center', padding: 24, gap: 14 }, header: { padding: 18, backgroundColor: '#fff', flexDirection: 'row', alignItems: 'center', gap: 12 }, headerGrow: { flex: 1 }, title: { fontSize: 21, fontWeight: '700', color: '#1e3a8a' }, sub: { fontSize: 12, color: '#64748b' }, list: { padding: 16, gap: 12 }, card: { backgroundColor: '#fff', padding: 15, borderRadius: 10, borderWidth: 1, borderColor: '#e2e8f0', gap: 8 }, menuCard: { backgroundColor: '#eff6ff', padding: 20, borderRadius: 12, borderWidth: 1, borderColor: '#bfdbfe', gap: 6 }, cardTitle: { fontWeight: '700', color: '#111827', fontSize: 16 }, muted: { color: '#64748b', marginTop: 6 }, input: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 8, padding: 11, marginTop: 9 }, section: { fontSize: 16, fontWeight: '700', marginTop: 8, color: '#1f2937' }, actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 }, pill: { paddingHorizontal: 8, paddingVertical: 5, borderRadius: 14, backgroundColor: '#e2e8f0' }, pillOn: { backgroundColor: '#86efac' }, footer: { padding: 16 }, filters: { paddingHorizontal: 12, paddingVertical: 10, gap: 8 }, chip: { paddingHorizontal: 12, paddingVertical: 8, backgroundColor: '#e2e8f0', borderRadius: 16 }, chipOn: { backgroundColor: '#93c5fd' }, studentRow: { flexDirection: 'row', alignItems: 'center', gap: 10 }, studentRowGrow: { flex: 1 }, badge: { fontSize: 12, fontWeight: '700', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 12, borderWidth: 1, overflow: 'hidden' }, flag: { backgroundColor: '#fff', padding: 16, borderRadius: 12, borderWidth: 2, gap: 6 }, flagLabel: { fontWeight: '700', color: '#111827', fontSize: 17 }, flagCircle: { width: 28, height: 28, borderRadius: 14, borderWidth: 2, alignItems: 'center', justifyContent: 'center' }, flagCheck: { fontSize: 15, fontWeight: '800' }, loginScroll: { flexGrow: 1, justifyContent: 'center', padding: 28 }, loginWrap: { flex: 1, backgroundColor: '#f8fafc' }, loginCard: { backgroundColor: '#fff', borderRadius: 22, padding: 28, maxWidth: 440, width: '100%', alignSelf: 'center', gap: 12, shadowColor: '#0f172a', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.08, shadowRadius: 24, elevation: 6 }, logo: { width: 84, height: 84, borderRadius: 24, backgroundColor: '#eff6ff', alignItems: 'center', justifyContent: 'center', alignSelf: 'center', fontSize: 44, marginBottom: 4 }, loginTitle: { fontSize: 28, fontWeight: '800', color: '#1e3a8a', textAlign: 'center' }, loginSubtitle: { fontSize: 15, color: '#475569', textAlign: 'center' }, loginHint: { color: '#64748b', textAlign: 'center', marginTop: 2 }, fieldBlock: { gap: 2 }, fieldLabel: { fontSize: 13, fontWeight: '600', color: '#334155' }, input: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 10, padding: 13, fontSize: 15 }, passwordRow: { flexDirection: 'row', alignItems: 'center', gap: 8 }, passwordInput: { flex: 1 }, eyeBtn: { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 10, paddingHorizontal: 10, paddingVertical: 11, backgroundColor: '#f8fafc' }, eyeText: { fontSize: 16 }, eyeLabel: { fontSize: 13, fontWeight: '600', color: '#334155' }, passwordHint: { fontSize: 12, color: '#2563eb', marginTop: 4 }, primaryBtn: { backgroundColor: '#1e3a8a', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 6 }, primaryBtnDisabled: { backgroundColor: '#94a3b8' }, primaryBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' }, bioBtn: { backgroundColor: '#fef4cd', borderWidth: 1, borderColor: '#fbbf24', padding: 14, borderRadius: 12, alignItems: 'center', marginTop: 4 }, bioBtnText: { color: '#92400e', fontSize: 14, fontWeight: '700' }, sessionBox: { backgroundColor: '#eff6ff', borderRadius: 12, padding: 12, gap: 6, marginTop: 4 }, bioRow: { flexDirection: 'row', alignItems: 'center', gap: 12 }, bioRowGrow: { flex: 1 }, dateRow: { flexDirection: 'row', alignItems: 'center', gap: 8 }, dateInput: { flex: 1 }, calendarBtn: { backgroundColor: '#eff6ff', borderWidth: 1, borderColor: '#bfdbfe', borderRadius: 10, paddingVertical: 11, paddingHorizontal: 14 }, calendarBtnText: { fontSize: 20 } });
