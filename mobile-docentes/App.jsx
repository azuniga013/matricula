import { useEffect, useMemo, useState } from 'react';
import Constants from 'expo-constants';
import {
  ActivityIndicator,
  Alert,
  Button,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import * as LocalAuthentication from 'expo-local-authentication';
import NetInfo from '@react-native-community/netinfo';
import {
  attendanceForOffer,
  clearBiometricCredentials,
  currentUser,
  grades,
  loadBiometricCredentials,
  login,
  logout,
  offers,
  saveAttendance,
  saveBiometricCredentials,
  saveGrades,
  students,
  updateWhatsappPeriodLink,
} from './src/api';
import {
  cachedAttendance,
  cachedGrades,
  cachedOffers,
  cachedStudents,
  clearAcademicCache,
  clearLocalData,
  initDatabase,
  markError,
  pending,
  queue,
  removePending,
  replaceAttendance,
  replaceGrades,
  replaceOffers,
  replaceStudents,
} from './src/database';
import DocenteHome from './src/components/DocenteHome';

const today = () => new Date().toISOString().slice(0, 10);
const toDateInput = (value) => {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value || '');
  if (!match) return new Date();
  return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
};
const fromDateInput = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const fullName = (student) => `${student.nombre || ''} ${student.apellido || ''}`.trim();
const offersForTeacher = (offers, docenteId) => {
  if (!docenteId) return [];
  const offersWithTeacher = offers.filter((offer) => offer && offer.docente_id !== undefined && offer.docente_id !== null);
  if (!offersWithTeacher.length) return offers.filter(Boolean);
  return offersWithTeacher.filter((offer) => String(offer.docente_id) === String(docenteId));
};
const hasLegacyOffersWithoutTeacher = (offers) => offers.some((offer) => offer && (offer.docente_id === undefined || offer.docente_id === null));

const MODULES = [
  {
    id: 'alumnos',
    title: 'Buscar Alumno',
    detail: 'Busque si un estudiante está en alguno de sus horarios asignados.',
    icon: 'search-outline',
    iconBg: '#ede9fe',
    iconColor: '#6d28d9',
  },
  {
    id: 'ofertas',
    title: 'Ofertas Académicas',
    detail: 'Consulte horarios, estudiantes y link de WhatsApp del período.',
    icon: 'library-outline',
    iconBg: '#dbeafe',
    iconColor: '#1d4ed8',
  },
  {
    id: 'asistencia',
    title: 'Asistencia Diaria',
    detail: 'Pase lista por horario y fecha con apoyo offline.',
    icon: 'checkmark-done-outline',
    iconBg: '#dcfce7',
    iconColor: '#15803d',
  },
  {
    id: 'calificaciones',
    title: 'Calificaciones',
    detail: 'Registre notas finales y faltas por estudiante.',
    icon: 'school-outline',
    iconBg: '#fef3c7',
    iconColor: '#b45309',
  },
  {
    id: 'sincronizacion',
    title: 'Sincronización',
    detail: 'Revise pendientes, conflictos y reintentos locales.',
    icon: 'sync-outline',
    iconBg: '#fee2e2',
    iconColor: '#b91c1c',
  },
];

const ATT_STATES = [
  { value: 'presente', label: 'Presente', note: 'Asistió normalmente', color: '#16a34a' },
  { value: 'falta', label: 'Falta', note: 'No asistió', color: '#dc2626' },
  { value: 'justificada', label: 'Justificada', note: 'Ausencia con justificación', color: '#d97706' },
  { value: 'tardanza', label: 'Tardanza', note: 'Llegó tarde', color: '#2563eb' },
];

const estadoInfo = (value) => ATT_STATES.find((item) => item.value === value) || ATT_STATES[0];
const estadoBadge = (value) => {
  const item = estadoInfo(value);
  return {
    label: item.label,
    badge: {
      backgroundColor: `${item.color}1a`,
      color: item.color,
      borderColor: item.color,
    },
  };
};

const buildAttendanceState = (studentsRows, saved = []) => {
  const savedByMatricula = Object.fromEntries(saved.map((row) => [row.matricula_id, row]));
  return Object.fromEntries(
    studentsRows.map((row) => {
      const savedRow = savedByMatricula[row.matricula_id];
      return [row.matricula_id, { estado: savedRow?.estado || 'presente', observacion: savedRow?.observacion || '' }];
    })
  );
};

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

    if (operation.tipo === 'calificacion') {
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
      const [hardware, enrolled] = await Promise.all([
        LocalAuthentication.hasHardwareAsync(),
        LocalAuthentication.isEnrolledAsync(),
      ]);
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

    currentUser()
      .then(async (profile) => {
        if (!profile.docente_id) throw new Error('Esta cuenta no está vinculada a un docente');
        const localOffers = cachedOffers();
        if (hasLegacyOffersWithoutTeacher(localOffers)) {
          clearAcademicCache();
          setItems([]);
        }
        setUser(profile);
        setItems(offersForTeacher(cachedOffers(), profile.docente_id));
        if (Boolean(await NetInfo.fetch().then((state) => state.isConnected))) {
          await refresh(null, profile.docente_id);
        }
      })
      .catch(() => {})
      .finally(() => setLoading(false));

    return unsubscribe;
  }, []);

  const pendingCount = useMemo(() => (databaseReady ? pending().length : 0), [databaseReady, items, selected, attendance, gradeRows, syncing, pendingVersion]);
  const pendingRows = useMemo(() => (databaseReady ? pending() : []), [databaseReady, items, selected, attendance, gradeRows, syncing, pendingVersion]);
  const recentErrors = useMemo(() => pendingRows.filter((item) => item.ultimo_error).slice(0, 3), [pendingRows]);

  const teacherOffers = useMemo(() => offersForTeacher(items, user?.docente_id), [items, user?.docente_id]);

  const periods = useMemo(
    () =>
      Object.values(
        teacherOffers.reduce((all, offer) => {
          const period = offer.periodo_academico;
          if (period?.id) all[period.id] = period;
          return all;
        }, {})
      ).sort((a, b) => String(b.nombre).localeCompare(String(a.nombre))),
    [teacherOffers]
  );

  const visibleOffers = useMemo(
    () => (periodId ? teacherOffers.filter((item) => String(item.periodo_academico_id || item.periodo_academico?.id) === String(periodId)) : teacherOffers),
    [teacherOffers, periodId]
  );

  const dashboard = useMemo(() => {
    const studentsCount = teacherOffers.reduce((total, offer) => total + cachedStudents(offer.id).length, 0);
    const primaryPeriod = periods[0];
    return {
      offersCount: teacherOffers.length,
      studentsCount,
      periodsCount: periods.length,
      primaryPeriodLabel: primaryPeriod ? primaryPeriod.nombre || primaryPeriod.codigo || 'Período activo' : 'Sin períodos descargados',
    };
  }, [teacherOffers, periods]);

  async function refresh(filterPeriod = periodId, docenteId = user?.docente_id) {
    if (!online) {
      Alert.alert('Sin conexión', 'Se muestran los datos descargados anteriormente.');
      return;
    }

    setSyncing(true);
    try {
      const downloadedOffers = await offers(filterPeriod);
      if (filterPeriod) {
        const retained = cachedOffers().filter((item) => String(item.periodo_academico_id || item.periodo_academico?.id) !== String(filterPeriod));
        replaceOffers([...retained, ...downloadedOffers]);
      } else {
        replaceOffers(downloadedOffers);
      }

      for (const offer of downloadedOffers) {
        const [enrolled, gradeData] = await Promise.all([students(offer.id), grades(offer.id)]);
        replaceStudents(offer.id, enrolled);
        replaceGrades(offer.id, gradeData);
      }

      setItems(offersForTeacher(cachedOffers(), docenteId));
      if (selected) await openOffer(selected.id);
      setSyncSummary({ type: 'ok', message: 'Datos descargados correctamente.' });
    } catch (error) {
      Alert.alert('No se pudo sincronizar', error.message);
      setSyncSummary({ type: 'error', message: error.message });
    } finally {
      setSyncing(false);
    }
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
          if (operation.tipo === 'calificacion') await saveGrades(operation.uuid, operation.oferta_id, data);
          removePending(operation.uuid);
          setPendingVersion((value) => value + 1);
          procesadas++;
        } catch (error) {
          markError(operation.uuid, error.message);
          errorSincronizacion = error.message || 'El servidor no pudo guardar la información.';
          if (error.body?.codigo_error) {
            errorSincronizacion = `${error.body.codigo_error}: ${error.body.mensaje || errorSincronizacion}`;
          }
          if (error.status === 401) {
            await logout();
            setUser(null);
            setModule(null);
            setSelected(null);
            setItems([]);
            errorSincronizacion = 'La sesión expiró. Vuelva a iniciar sesión para continuar.';
            break;
          }
          if (error.status === 422) {
            errorSincronizacion = error.body?.codigo_error
              ? `${error.body.codigo_error}: ${error.body.mensaje || 'El servidor rechazó el guardado.'}`
              : 'El servidor rechazó el guardado (respuesta local pendiente). Revisa la oferta; si no tiene estudiantes, descárgala con internet.';
            continue;
          }
          if (error.status === 404) {
            errorSincronizacion = error.body?.codigo_error
              ? `${error.body.codigo_error}: ${error.body.mensaje || 'Registro no encontrado.'}`
              : 'Algunos registros ya no existen en el servidor. Se conservan para revisar.';
            continue;
          }
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
    } finally {
      setSyncing(false);
    }
  }

  async function openOffer(offerId) {
    const offer = items.find((item) => item.id === offerId) || cachedOffers().find((item) => item.id === offerId);
    if (!offer) return;

    setSelected(offer);
    const enrolled = cachedStudents(offerId);
    const gradeData = cachedGrades(offerId);
    setStudentsForOffer(enrolled);
    setGradeRows(
      Object.fromEntries(
        gradeData.map((row) => [row.estudiante_id, { nota_final: row.nota_final ?? '', faltas: row.faltas ?? 0, observaciones: row.observaciones ?? '' }])
      )
    );

    const priorAttendance = cachedAttendance(offerId, date);
    setAttendance(buildAttendanceState(enrolled, priorAttendance));

    if (online) {
      try {
        const [freshStudents, freshGrades, freshAttendance] = await Promise.all([
          students(offerId),
          grades(offerId),
          attendanceForOffer(offerId, date),
        ]);
        replaceStudents(offerId, freshStudents);
        replaceGrades(offerId, freshGrades);
        replaceAttendance(offerId, date, freshAttendance);
        setStudentsForOffer(freshStudents);
        setGradeRows(
          Object.fromEntries(
            freshGrades.map((row) => [row.estudiante_id, { nota_final: row.nota_final ?? '', faltas: row.faltas ?? 0, observaciones: row.observaciones ?? '' }])
          )
        );
        setAttendance(buildAttendanceState(freshStudents, freshAttendance));
      } catch (_) {
        // conservar copia offline
      }
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
    } catch (_) {
      // conservar copia local
    }
  }

  async function saveAttendanceLocally() {
    if (!studentsForOffer.length) {
      Alert.alert('Sin estudiantes', 'Esta oferta no tiene estudiantes matriculados para registrar.');
      return;
    }

    const asistencias = studentsForOffer.map((student) => ({
      matricula_id: student.matricula_id,
      estado: attendance[student.matricula_id]?.estado || 'presente',
      cuenta_como_falta: ['falta'].includes(attendance[student.matricula_id]?.estado || 'presente'),
      observacion: attendance[student.matricula_id]?.observacion || null,
    }));

    replaceAttendance(selected.id, date, asistencias);
    asistencias.forEach((item) => queue('asistencia', selected.id, { fecha: date, ...item }));

    if (!online) {
      Alert.alert('Guardado local', 'La asistencia queda pendiente de sincronización hasta recuperar internet.');
      return;
    }

    const resultado = await synchronizeQueue();
    if (resultado.error) {
      Alert.alert('Asistencia pendiente', `${resultado.error} Se conserva localmente para reintentar.`);
      return;
    }

    Alert.alert('Asistencia sincronizada', `${resultado.procesadas} operación(es) guardada(s) en el servidor.`);
  }

  async function saveGradesLocally() {
    const calificaciones = studentsForOffer.map((student) => {
      const id = student.estudiante_id || student.id;
      return {
        estudiante_id: id,
        nota_final: gradeRows[id]?.nota_final || null,
        faltas: Number(gradeRows[id]?.faltas || 0),
        observaciones: gradeRows[id]?.observaciones || null,
      };
    });

    calificaciones.forEach((item) => queue('calificacion', selected.id, item));
    if (!online) {
      Alert.alert('Guardado local', 'Las calificaciones quedan pendientes de sincronización hasta recuperar internet.');
      return;
    }

    const resultado = await synchronizeQueue();
    if (resultado.error) {
      Alert.alert('Calificaciones pendientes', `${resultado.error} Se conservan localmente para reintentar.`);
      return;
    }

    Alert.alert('Calificaciones sincronizadas', `${resultado.procesadas} operación(es) guardada(s) en el servidor.`);
  }

  async function submitLogin() {
    setLoading(true);
    try {
      const profile = await login(email.trim(), password);
      if (bioGuardado) await saveBiometricCredentials(email.trim(), password);
      else await clearBiometricCredentials();

      setUser(profile);
      await refresh(null, profile.docente_id);
    } catch (error) {
      Alert.alert('No se pudo iniciar sesión', error.message);
    } finally {
      setLoading(false);
    }
  }

  async function bioLogin() {
    if (!bioGuardado) {
      Alert.alert('Sin acceso biométrico', 'Active "Acceso con huella" y luego inicie sesión una vez para guardar el acceso.');
      return;
    }

    setBioLoading(true);
    try {
      const result = await LocalAuthentication.authenticateAsync({
        promptMessage: 'Verifique su identidad para entrar al Portal Docente',
        cancelLabel: 'Cancelar',
        disableDeviceFallback: false,
      });
      if (!result?.success) return;

      const creds = await loadBiometricCredentials();
      if (!creds) return;

      const profile = await login(creds.email, creds.password);
      setUser(profile);
      await refresh(null, profile.docente_id);
    } catch (error) {
      Alert.alert('No se pudo entrar con huella', error.message || 'Inténtelo de nuevo.');
    } finally {
      setBioLoading(false);
    }
  }

  async function closeSession() {
    await logout();
    clearLocalData();
    setUser(null);
    setModule(null);
    setSelected(null);
    setItems([]);
  }

  if (loading) return <Centered text="Preparando aplicación docente..." />;
  if (!user) {
    return (
      <Login
        email={email}
        setEmail={setEmail}
        password={password}
        setPassword={setPassword}
        submit={submitLogin}
        bioAvailable={bioAvailable}
        bioGuardado={bioGuardado}
        setBioGuardado={setBioGuardado}
        bioLoading={bioLoading}
        bioLogin={bioLogin}
      />
    );
  }
  if (selected) {
    return (
      <StudentList
        module={module}
        offer={selected}
        students={studentsForOffer}
        attendance={attendance}
        setAttendance={setAttendance}
        grades={gradeRows}
        setGrades={setGradeRows}
        date={date}
        setDate={setDate}
        online={online}
        saveAttendance={saveAttendanceLocally}
        saveGrades={saveGradesLocally}
        changeDate={changeAttendanceDate}
        back={() => setSelected(null)}
      />
    );
  }
  if (!module) {
    return (
      <DocenteHome
        user={user}
        online={online}
        pendingCount={pendingCount}
        recentErrors={recentErrors}
        syncSummary={syncSummary}
        modules={MODULES}
        open={setModule}
        sync={() => refresh(null)}
        syncing={syncing}
        logout={closeSession}
        dashboard={dashboard}
      />
    );
  }
  if (module === 'sincronizacion') {
    return (
      <SyncQueueScreen
        pendingRows={pendingRows}
        online={online}
        syncing={syncing}
        syncSummary={syncSummary}
        synchronize={synchronizeQueue}
        back={() => setModule(null)}
        setPendingVersion={setPendingVersion}
        setSyncSummary={setSyncSummary}
      />
    );
  }
  if (module === 'alumnos') {
    return <SearchStudentsScreen offers={teacherOffers} openOffer={openOffer} back={() => setModule(null)} />;
  }
  return (
    <OfferList
      module={MODULES.find((item) => item.id === module)}
      offers={visibleOffers}
      periods={periods}
      periodId={periodId}
      selectPeriod={(id) => {
        setPeriodId(id);
        if (online) refresh(id);
      }}
      open={openOffer}
      back={() => setModule(null)}
      sync={() => refresh(periodId)}
      syncing={syncing}
    />
  );
}

function SearchStudentsScreen({ offers, openOffer, back }) {
  const [query, setQuery] = useState('');
  const referenceDate = today();

  const results = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (term.length < 2) return [];

    const grouped = new Map();
    for (const offer of offers) {
      const enrolled = cachedStudents(offer.id) || [];
      for (const student of enrolled) {
        const studentName = fullName(student);
        const haystack = [student.codigo, studentName, student.email].filter(Boolean).join(' ').toLowerCase();
        if (!haystack.includes(term)) continue;

        const attendanceRows = cachedAttendance(offer.id, referenceDate) || [];
        const attendanceRow = attendanceRows.find((row) => String(row.matricula_id) === String(student.matricula_id));
        const gradeRows = cachedGrades(offer.id) || [];
        const gradeRow = gradeRows.find((row) => String(row.estudiante_id) === String(student.estudiante_id || student.id));

        const studentKey = String(student.estudiante_id || student.id || student.matricula_id);
        if (!grouped.has(studentKey)) {
          grouped.set(studentKey, {
            key: studentKey,
            student,
            studentName,
            offers: [],
          });
        }

        grouped.get(studentKey).offers.push({
          offer,
          attendanceLoaded: Boolean(attendanceRow),
          attendanceStatus: attendanceRow?.estado || null,
          gradeLoaded: Boolean(
            gradeRow && (
              gradeRow.nota_final !== null
              && gradeRow.nota_final !== undefined
              && String(gradeRow.nota_final) !== ''
            )
          ),
          faltasLoaded: Boolean(gradeRow && gradeRow.faltas !== null && gradeRow.faltas !== undefined),
        });
      }
    }

    return Array.from(grouped.values()).sort((a, b) => a.studentName.localeCompare(b.studentName));
  }, [offers, query]);

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Button title="Menú" onPress={back} />
        <View style={styles.headerGrow}>
          <Text style={styles.title}>Buscar Alumno</Text>
          <Text style={styles.sub}>Consulte si el alumno está en alguno de sus horarios</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.list}>
        <View style={extraStyles.infoCard}>
          <Text style={extraStyles.infoTitle}>Búsqueda por alumno</Text>
          <Text style={extraStyles.infoText}>Escriba nombre, apellido, código o correo. La búsqueda usa las ofertas que ya tiene sincronizadas en este dispositivo.</Text>
        </View>

        <TextInput
          style={extraStyles.searchInput}
          placeholder="Ej. Ana, EST-2026-0001, correo@dominio.com"
          value={query}
          onChangeText={setQuery}
          autoCorrect={false}
          autoCapitalize="none"
        />

        {query.trim().length < 2 ? (
          <View style={extraStyles.emptyCard}>
            <Text style={styles.cardTitle}>Ingrese al menos 2 caracteres</Text>
            <Text style={styles.muted}>La búsqueda se activará cuando escriba suficiente información del alumno.</Text>
          </View>
        ) : null}

        {query.trim().length >= 2 && results.length === 0 ? (
          <View style={extraStyles.emptyCard}>
            <Text style={styles.cardTitle}>Sin coincidencias</Text>
            <Text style={styles.muted}>No encontramos ese alumno dentro de sus horarios descargados.</Text>
          </View>
        ) : null}

        {results.map(({ key, student, studentName, offers: matchedOffers }) => (
          <View key={key} style={extraStyles.offerCard}>
            <Text style={styles.cardTitle}>{student.codigo || 'Alumno'} · {studentName || 'Sin nombre'}</Text>
            <Text style={styles.muted}>{student.email || 'Sin correo registrado'}</Text>
            <Text style={extraStyles.helperText}>
              Aparece en {matchedOffers.length} horario(s) asignado(s) a este docente.
            </Text>
            <Text style={extraStyles.helperText}>Referencia de asistencia: {referenceDate}</Text>

            {matchedOffers.map(({ offer, attendanceLoaded, attendanceStatus, gradeLoaded, faltasLoaded }) => (
              <View key={`${key}-${offer.id}`} style={extraStyles.matchCard}>
                <View style={extraStyles.offerMetaRow}>
                  <Text style={extraStyles.offerTag}>{offer.horario?.nombre || 'Sin horario'}</Text>
                  <Text style={extraStyles.offerTagSoft}>{offer.periodo_academico?.codigo || offer.periodo_academico?.nombre || 'Período'}</Text>
                </View>
                <Text style={extraStyles.helperText}>Oferta: {offer.codigo} · {offer.nivel_academico?.nombre || 'Sin nivel'}</Text>
                <View style={extraStyles.offerMetaRow}>
                  <Text style={attendanceLoaded ? extraStyles.statusOk : extraStyles.statusPending}>
                    {attendanceLoaded ? `Asistencia: ${attendanceStatus || 'cargada'}` : 'Asistencia hoy: pendiente'}
                  </Text>
                  <Text style={gradeLoaded || faltasLoaded ? extraStyles.statusOk : extraStyles.statusPending}>
                    {gradeLoaded || faltasLoaded ? 'Calificación: registrada' : 'Calificación: pendiente'}
                  </Text>
                </View>
                <TouchableOpacity style={extraStyles.inlineOpenBtn} onPress={() => openOffer(offer.id)} activeOpacity={0.85}>
                  <Text style={extraStyles.inlineOpenBtnText}>Abrir horario</Text>
                </TouchableOpacity>
              </View>
            ))}
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

function SyncQueueScreen({ pendingRows, online, syncing, syncSummary, synchronize, back, setPendingVersion, setSyncSummary }) {
  async function retryNow() {
    const resultado = await synchronize();
    if (resultado.error) {
      Alert.alert('Sincronización incompleta', resultado.error);
      return;
    }
    Alert.alert('Sincronización completada', `${resultado.procesadas} operación(es) aplicadas.`);
  }

  function discardPending(item) {
    Alert.alert('Descartar pendiente', 'Esta operación se eliminará del dispositivo y no volverá a sincronizarse. ¿Desea continuar?', [
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
    ]);
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Button title="Menú" onPress={back} />
        <View style={styles.headerGrow}>
          <Text style={styles.title}>Sincronización</Text>
          <Text style={styles.sub}>{online ? 'Con conexión disponible' : 'Sin conexión'} · {pendingRows.length} pendiente(s)</Text>
        </View>
        <Button title={syncing ? '...' : 'Reintentar'} disabled={syncing || !pendingRows.length} onPress={retryNow} />
      </View>

      <ScrollView contentContainerStyle={styles.list}>
        {syncSummary ? <View style={styles.card}><Text style={styles.cardTitle}>Último resultado</Text><Text style={styles.muted}>{syncSummary.message}</Text></View> : null}
        {!pendingRows.length ? <View style={styles.card}><Text style={styles.cardTitle}>Sin pendientes</Text><Text style={styles.muted}>No hay operaciones locales esperando sincronización.</Text></View> : null}
        {pendingRows.map((item) => (
          <View key={item.uuid} style={styles.card}>
            <Text style={styles.cardTitle}>{item.tipo === 'asistencia' ? 'Asistencia' : 'Calificación'}</Text>
            <Text style={styles.muted}>Resumen: {summarizePendingPayload(item)}</Text>
            <Text style={styles.muted}>UUID: {item.uuid}</Text>
            <Text style={styles.muted}>Oferta: {item.oferta_id}</Text>
            <Text style={styles.muted}>Creado: {item.creado_en}</Text>
            <Text style={styles.muted}>Reintentos: {item.reintentos}</Text>
            <Text style={styles.muted}>Estado: {item.ultimo_error ? (String(item.ultimo_error).toLowerCase().includes('conflicto') ? 'Conflicto' : 'Con error') : 'Pendiente'}</Text>
            {item.ultimo_error ? <Text style={styles.muted}>Detalle: {item.ultimo_error}</Text> : null}
            <View style={styles.pendingActions}>
              <Button title="Descartar" color="#b91c1c" onPress={() => discardPending(item)} />
            </View>
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

function PeriodFilter({ periods, periodId, selectPeriod }) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
      <TouchableOpacity style={[styles.chip, !periodId && styles.chipOn]} onPress={() => selectPeriod(null)}>
        <Text>Todos</Text>
      </TouchableOpacity>
      {periods.map((period) => (
        <TouchableOpacity key={period.id} style={[styles.chip, String(periodId) === String(period.id) && styles.chipOn]} onPress={() => selectPeriod(period.id)}>
          <Text>{period.codigo || period.nombre}</Text>
        </TouchableOpacity>
      ))}
    </ScrollView>
  );
}

function OfferList({ module, offers, periods, periodId, selectPeriod, open, back, sync, syncing }) {
  const [links, setLinks] = useState({});
  const [names, setNames] = useState({});
  const [savingId, setSavingId] = useState(null);
  const [query, setQuery] = useState('');

  useEffect(() => {
    setLinks(Object.fromEntries(offers.map((offer) => [offer.id, offer.whatsapp_link_periodo || ''])));
    setNames(Object.fromEntries(offers.map((offer) => [offer.id, offer.whatsapp_grupo_nombre || ''])));
  }, [offers]);

  const filteredOffers = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (!term) return offers;
    return offers.filter((offer) => [offer.codigo, offer.nivel_academico?.nombre, offer.periodo_academico?.nombre, offer.horario?.nombre, offer.whatsapp_grupo_nombre]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(term)));
  }, [offers, query]);

  async function saveWhatsappLink(offer) {
    setSavingId(offer.id);
    try {
      const updated = await updateWhatsappPeriodLink(offer.id, links[offer.id] || null, names[offer.id] || null);
      offer.whatsapp_grupo_nombre = updated?.whatsapp_grupo_nombre || null;
      offer.whatsapp_link_periodo = updated?.whatsapp_link_periodo || null;
      setNames((prev) => ({ ...prev, [offer.id]: offer.whatsapp_grupo_nombre || '' }));
      setLinks((prev) => ({ ...prev, [offer.id]: offer.whatsapp_link_periodo || '' }));
      Alert.alert('WhatsApp', 'Configuración del horario guardada correctamente.');
    } catch (error) {
      Alert.alert('No se pudo guardar', error.message || 'Inténtelo de nuevo.');
    } finally {
      setSavingId(null);
    }
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Button title="Menú" onPress={back} />
        <View style={styles.headerGrow}>
          <Text style={styles.title}>{module.title}</Text>
          <Text style={styles.sub}>Seleccione período y horario</Text>
        </View>
        <Button title={syncing ? '...' : 'Actualizar'} disabled={syncing} onPress={sync} />
      </View>

      <FlatList
        data={filteredOffers}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.list}
        ListHeaderComponent={
          <>
            <View style={extraStyles.infoCard}>
              <Text style={extraStyles.infoTitle}>{module.title}</Text>
              <Text style={extraStyles.infoText}>
                {module?.id === 'ofertas'
                  ? 'Consulte cada horario, revise el nombre funcional de WhatsApp y mantenga actualizado el link del período.'
                  : 'Elija el horario en el que desea trabajar y continúe con el listado de estudiantes.'}
              </Text>
              <View style={extraStyles.metricRow}>
                <View style={extraStyles.metricPill}>
                  <Text style={extraStyles.metricPillLabel}>Períodos</Text>
                  <Text style={extraStyles.metricPillValue}>{periods.length}</Text>
                </View>
                <View style={extraStyles.metricPill}>
                  <Text style={extraStyles.metricPillLabel}>Horarios</Text>
                  <Text style={extraStyles.metricPillValue}>{filteredOffers.length}</Text>
                </View>
              </View>
            </View>

            <TextInput
              style={extraStyles.searchInput}
              placeholder="Buscar por código, nivel, horario o período"
              value={query}
              onChangeText={setQuery}
              autoCorrect={false}
            />

            <PeriodFilter periods={periods} periodId={periodId} selectPeriod={selectPeriod} />
          </>
        }
        ListEmptyComponent={
          <View style={extraStyles.emptyCard}>
            <Text style={styles.cardTitle}>Sin resultados</Text>
            <Text style={styles.muted}>No hay horarios para el filtro actual.</Text>
          </View>
        }
        renderItem={({ item }) => (
          <View style={extraStyles.offerCard}>
            <TouchableOpacity onPress={() => open(item.id)} activeOpacity={0.85}>
              <View style={extraStyles.offerHeader}>
                <View style={extraStyles.offerHeaderGrow}>
                  <Text style={styles.cardTitle}>{item.codigo} · {item.nivel_academico?.nombre || 'Oferta'}</Text>
                  <Text style={styles.muted}>{item.periodo_academico?.nombre || 'Sin período'} · {item.horario?.nombre || 'Sin horario'}</Text>
                </View>
                <View style={extraStyles.offerAction}>
                  <Text style={extraStyles.offerActionText}>Abrir</Text>
                </View>
              </View>

              <View style={extraStyles.offerMetaRow}>
                <Text style={extraStyles.offerTag}>{item.horario?.nombre || 'Horario pendiente'}</Text>
                <Text style={extraStyles.offerTagSoft}>{item.periodo_academico?.codigo || item.periodo_academico?.nombre || 'Período'}</Text>
              </View>
            </TouchableOpacity>

            {module?.id === 'ofertas' ? (
              <View style={extraStyles.whatsappPanel}>
                <Text style={styles.fieldLabel}>Nombre del horario en WhatsApp</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Ej. Inglés 1 Intensivo Matutino SPS"
                  value={names[item.id] || ''}
                  onChangeText={(value) => setNames((prev) => ({ ...prev, [item.id]: value }))}
                  autoCorrect={false}
                />
                <Text style={extraStyles.helperText}>Este es el nombre funcional que verá el docente para identificar el horario en WhatsApp.</Text>

                <Text style={styles.fieldLabel}>Link WhatsApp del período</Text>
                <TextInput
                  style={styles.input}
                  placeholder="https://chat.whatsapp.com/..."
                  value={links[item.id] || ''}
                  onChangeText={(value) => setLinks((prev) => ({ ...prev, [item.id]: value }))}
                  autoCapitalize="none"
                  autoCorrect={false}
                />

                <TouchableOpacity
                  style={[extraStyles.primaryBlockBtn, savingId === item.id && extraStyles.primaryBlockBtnDisabled]}
                  disabled={savingId === item.id}
                  onPress={() => saveWhatsappLink(item)}
                  activeOpacity={0.85}
                >
                  <Text style={extraStyles.primaryBlockBtnText}>{savingId === item.id ? 'Guardando...' : 'Guardar configuración de WhatsApp'}</Text>
                </TouchableOpacity>

                {!names[item.id] ? <Text style={extraStyles.helperText}>Puede dejar solo el link, pero se recomienda definir también un nombre claro para el horario.</Text> : null}
              </View>
            ) : null}
          </View>
        )}
      />
    </SafeAreaView>
  );
}

function StudentList({ module, offer, students, attendance, setAttendance, grades, setGrades, date, setDate, online, saveAttendance, saveGrades, changeDate, back }) {
  const isAttendance = module === 'asistencia';
  const isGrades = module === 'calificaciones';
  const [marking, setMarking] = useState(null);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [refreshingDate, setRefreshingDate] = useState(false);
  const [query, setQuery] = useState('');

  const filteredStudents = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (!term) return students;
    return students.filter((student) => [student.codigo, fullName(student)].filter(Boolean).some((value) => String(value).toLowerCase().includes(term)));
  }, [students, query]);

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
      try {
        await changeDate(newDate);
      } finally {
        setRefreshingDate(false);
      }
    }
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Button title="Ofertas" onPress={back} />
        <View style={styles.headerGrow}>
          <Text style={styles.title}>{isAttendance ? 'Asistencia Diaria' : isGrades ? 'Calificaciones' : 'Estudiantes'}</Text>
          <Text style={styles.sub}>{offer.codigo} · {offer.periodo_academico?.nombre}</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.list}>
        <View style={extraStyles.infoCard}>
          <Text style={extraStyles.infoTitle}>{offer.nivel_academico?.nombre || 'Horario académico'}</Text>
          <Text style={extraStyles.infoText}>{offer.horario?.nombre || 'Sin horario'} · {students.length} estudiante(s) disponible(s).</Text>

          {isAttendance ? (
            <View style={extraStyles.datePanel}>
              <Text style={styles.fieldLabel}>Fecha de trabajo</Text>
              <View style={styles.dateRow}>
                <TextInput
                  style={[styles.input, styles.dateInput, extraStyles.inputNoMargin]}
                  value={date}
                  onChangeText={setDate}
                  placeholder="YYYY-MM-DD"
                  autoCorrect={false}
                />
                <TouchableOpacity style={styles.calendarBtn} onPress={() => setShowDatePicker(true)} activeOpacity={0.8}>
                  <Text style={extraStyles.calendarBtnText}>{refreshingDate ? '...' : 'Calendario'}</Text>
                </TouchableOpacity>
              </View>
              {showDatePicker ? <DateTimePicker value={toDateInput(date)} mode="date" display={Platform.OS === 'ios' ? 'spinner' : 'default'} onChange={pickDate} /> : null}
            </View>
          ) : null}

          {isGrades ? (
            <View style={extraStyles.metricRow}>
              <View style={extraStyles.metricPill}>
                <Text style={extraStyles.metricPillLabel}>Capturados</Text>
                <Text style={extraStyles.metricPillValue}>{filteredStudents.filter((student) => {
                  const id = student.estudiante_id || student.id;
                  const row = grades[id] || {};
                  return String(row.nota_final || '').trim() !== '' || String(row.faltas ?? '').trim() !== '' || String(row.observaciones || '').trim() !== '';
                }).length}</Text>
              </View>
              <View style={extraStyles.metricPill}>
                <Text style={extraStyles.metricPillLabel}>Pendientes</Text>
                <Text style={extraStyles.metricPillValue}>{filteredStudents.length - filteredStudents.filter((student) => {
                  const id = student.estudiante_id || student.id;
                  const row = grades[id] || {};
                  return String(row.nota_final || '').trim() !== '' || String(row.faltas ?? '').trim() !== '' || String(row.observaciones || '').trim() !== '';
                }).length}</Text>
              </View>
            </View>
          ) : null}
          {isGrades ? <Text style={extraStyles.helperText}>Ingrese nota final, faltas y observaciones. El grupo se guarda completo con un solo botón al final.</Text> : null}
        </View>

        <TextInput
          style={extraStyles.searchInput}
          placeholder="Buscar estudiante por código o nombre"
          value={query}
          onChangeText={setQuery}
          autoCorrect={false}
        />

        {filteredStudents.length === 0 ? (
          <View style={extraStyles.emptyCard}>
            <Text style={styles.cardTitle}>Sin estudiantes</Text>
            <Text style={styles.muted}>No hay coincidencias para la búsqueda actual.</Text>
          </View>
        ) : null}

        {filteredStudents.map((student) => {
          const id = student.estudiante_id || student.id;
          const row = grades[id] || {};

          if (isAttendance) {
            const selectedAttendance = attendance[student.matricula_id];
            return (
              <TouchableOpacity key={student.matricula_id} style={styles.card} onPress={() => setMarking(student)} activeOpacity={0.85}>
                <View style={extraStyles.studentHeadRow}>
                  <View style={extraStyles.studentIdentity}>
                    <Text style={styles.cardTitle}>{student.codigo || 'Sin código'}</Text>
                    <Text style={extraStyles.studentName}>{fullName(student) || 'Sin nombre'}</Text>
                  </View>
                  <View style={[styles.badge, estadoBadge(selectedAttendance?.estado).badge, extraStyles.statusBadgePill]}>
                    <Text style={{ color: estadoBadge(selectedAttendance?.estado).badge.color, fontWeight: '700', fontSize: 12 }}>
                      {estadoBadge(selectedAttendance?.estado).label}
                    </Text>
                  </View>
                </View>
                <Text style={extraStyles.helperText}>Toque para marcar presente, falta, justificada o tardanza.</Text>
              </TouchableOpacity>
            );
          }

          return (
            <View key={student.matricula_id} style={styles.card}>
              <View style={extraStyles.studentHeadRow}>
                <View style={extraStyles.studentIdentity}>
                  <Text style={styles.cardTitle}>{student.codigo || 'Sin código'}</Text>
                  <Text style={extraStyles.studentName}>{fullName(student) || 'Sin nombre'}</Text>
                </View>
                <View style={extraStyles.studentBadge}>
                  <Text style={extraStyles.studentBadgeText}>{student.matricula_id ? 'Matriculado' : 'Sin matrícula'}</Text>
                </View>
              </View>

              <View style={extraStyles.gradeGrid}>
                <View style={extraStyles.gradeField}>
                  <Text style={styles.fieldLabel}>Nota final</Text>
                  <TextInput
                    style={[styles.input, extraStyles.compactInput]}
                    placeholder="0-100"
                    keyboardType="decimal-pad"
                    value={String(row.nota_final ?? '')}
                    onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, nota_final: value } })}
                  />
                </View>
                <View style={extraStyles.gradeField}>
                  <Text style={styles.fieldLabel}>Faltas</Text>
                  <TextInput
                    style={[styles.input, extraStyles.compactInput]}
                    placeholder="0"
                    keyboardType="number-pad"
                    value={String(row.faltas ?? 0)}
                    onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, faltas: value } })}
                  />
                </View>
              </View>

              <View style={extraStyles.gradeField}>
                <Text style={styles.fieldLabel}>Observaciones</Text>
                <TextInput
                  style={[styles.input, extraStyles.notesInput]}
                  placeholder="Comentarios breves"
                  value={String(row.observaciones ?? '')}
                  onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, observaciones: value } })}
                  multiline
                />
              </View>

              <Text style={extraStyles.helperText}>Se guarda todo el grupo de una vez al final. Puede avanzar de alumno en alumno sin perder lo capturado.</Text>
            </View>
          );
        })}

        <TouchableOpacity style={extraStyles.primaryBlockBtn} onPress={isAttendance ? saveAttendance : saveGrades} activeOpacity={0.85}>
          <Text style={extraStyles.primaryBlockBtnText}>
            {isAttendance
              ? online
                ? 'Guardar y sincronizar asistencia'
                : 'Guardar asistencia local'
              : online
                ? 'Guardar y sincronizar calificaciones'
                : 'Guardar calificaciones locales'}
          </Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

function MarkingStudent({ offer, student, attendance, setAttendance, back }) {
  const matriculaId = student.matricula_id;
  const current = attendance[matriculaId] || { estado: 'presente', observacion: '' };
  const setEstado = (estado) => setAttendance({ ...attendance, [matriculaId]: { ...current, estado } });

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Button title="Volver" onPress={back} />
        <View style={styles.headerGrow}>
          <Text style={styles.title}>{fullName(student)}</Text>
          <Text style={styles.sub}>{student.codigo} · {offer.codigo}</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.list}>
        <View style={extraStyles.infoCard}>
          <Text style={extraStyles.infoTitle}>{student.codigo || 'Sin código'}</Text>
          <Text style={extraStyles.infoText}>{fullName(student) || 'Sin nombre'}</Text>
          <Text style={extraStyles.helperText}>{offer.codigo} · {offer.horario?.nombre || 'Horario'}</Text>
        </View>

        <Text style={styles.section}>Marcar asistencia</Text>
        {ATT_STATES.map((state) => (
          <TouchableOpacity
            key={state.value}
            style={[styles.flag, current.estado === state.value && { borderColor: state.color, backgroundColor: `${state.color}14` }]}
            onPress={() => setEstado(state.value)}
          >
            <View style={extraStyles.attendanceOptionRow}>
              <View style={styles.studentRowGrow}>
                <Text style={styles.flagLabel}>{state.label}</Text>
                <Text style={styles.muted}>{state.note}</Text>
              </View>
              <View style={[styles.flagCircle, { borderColor: state.color }, current.estado === state.value && { backgroundColor: state.color }]}>
                {current.estado === state.value ? <Text style={[styles.flagCheck, { color: '#fff' }]}>✓</Text> : null}
              </View>
            </View>
          </TouchableOpacity>
        ))}

        <Text style={styles.muted}>Seleccione el estado y regrese para continuar. Luego presione Guardar y sincronizar al final de la lista.</Text>
        <TouchableOpacity style={extraStyles.primaryBlockBtn} onPress={() => { setEstado(current.estado); back(); }} activeOpacity={0.85}>
          <Text style={extraStyles.primaryBlockBtnText}>Guardar estado</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

function Login({ email, setEmail, password, setPassword, submit, bioAvailable, bioGuardado, setBioGuardado, bioLoading, bioLogin }) {
  const [showPassword, setShowPassword] = useState(false);
  const appVersion = Constants.expoConfig?.version || '0.0.0';
  const appVersionCode = Constants.expoConfig?.android?.versionCode || '-';

  return (
    <KeyboardAvoidingView style={styles.loginWrap} behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={40}>
      <ScrollView contentContainerStyle={styles.loginScroll} keyboardShouldPersistTaps="handled">
        <View style={styles.loginCard}>
          <View style={styles.logo}>{'\u{1F393}'}</View>
          <Text style={styles.loginTitle}>Cursos SVP</Text>
          <Text style={styles.loginSubtitle}>Portal Docente</Text>
          <Text style={styles.loginHint}>Ingrese con su cuenta administrativa vinculada como docente.</Text>
          <Text style={styles.loginVersion}>Versión {appVersion} · Build {appVersionCode}</Text>

          <View style={styles.fieldBlock}>
            <Text style={styles.fieldLabel}>Correo</Text>
            <TextInput
              style={styles.input}
              placeholder="correo@dominio.com"
              placeholderTextColor="#94a3b8"
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType="email-address"
              value={email}
              onChangeText={setEmail}
            />
          </View>

          <View style={styles.fieldBlock}>
            <Text style={styles.fieldLabel}>Contraseña</Text>
            <View style={styles.passwordRow}>
              <TextInput
                style={[styles.input, styles.passwordInput]}
                placeholder="••••••••"
                placeholderTextColor="#94a3b8"
                secureTextEntry={!showPassword}
                value={password}
                onChangeText={setPassword}
                onSubmitEditing={submit}
                returnKeyType="go"
                autoCorrect={false}
                autoCapitalize="none"
              />
              <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowPassword((value) => !value)} activeOpacity={0.7}>
                <Text style={styles.eyeText}>{showPassword ? '\u{1F441}\uFE0F' : '\u{1F441}'}</Text>
                <Text style={styles.eyeLabel}>{showPassword ? 'Ocultar' : 'Ver'}</Text>
              </TouchableOpacity>
            </View>
            {showPassword && password.length > 0 ? <Text style={styles.passwordHint}>Se muestra lo que escribió; puede ocultar con "Ocultar".</Text> : null}
          </View>

          <TouchableOpacity style={[styles.primaryBtn, (!email || !password) && styles.primaryBtnDisabled]} onPress={submit} disabled={!email || password.length === 0} activeOpacity={0.85}>
            <Text style={styles.primaryBtnText}>Iniciar sesión</Text>
          </TouchableOpacity>

          {bioAvailable ? (
            <View style={styles.sessionBox}>
              <View style={styles.bioRow}>
                <View style={styles.bioRowGrow}>
                  <Text style={styles.fieldLabel}>Acceso con huella</Text>
                  <Text style={styles.muted}>
                    {bioGuardado
                      ? 'Guardado en este dispositivo. Podrá entrar sin escribir la contraseña.'
                      : 'Guarde su cuenta en este dispositivo para entrar con un toque.'}
                  </Text>
                </View>
                <Switch
                  value={bioGuardado}
                  onValueChange={(value) => {
                    setBioGuardado(value);
                    if (!value) clearBiometricCredentials().catch(() => {});
                    Alert.alert(
                      value ? 'Acceso biométrico activado' : 'Acceso biométrico desactivado',
                      value
                        ? 'Al iniciar sesión se guardará su cuenta para entrar con huella o rostro.'
                        : 'Ya no se guardará su cuenta para entrar con huella.'
                    );
                  }}
                  trackColor={{ false: '#cbd5e1', true: '#bfdbfe' }}
                  thumbColor={bioGuardado ? '#1e3a8a' : '#f1f5f9'}
                />
              </View>
            </View>
          ) : null}

          {bioAvailable && bioGuardado ? (
            <TouchableOpacity style={styles.bioBtn} onPress={bioLogin} disabled={bioLoading} activeOpacity={0.85}>
              <Text style={styles.bioBtnText}>{bioLoading ? 'Verificando huella…' : 'Entrar con huella o rostro'}</Text>
            </TouchableOpacity>
          ) : null}
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function Centered({ text }) {
  return (
    <View style={styles.center}>
      <ActivityIndicator size="large" />
      <Text style={styles.muted}>{text}</Text>
    </View>
  );
}

const extraStyles = StyleSheet.create({
  infoCard: { backgroundColor: '#eff6ff', borderWidth: 1, borderColor: '#bfdbfe', borderRadius: 18, padding: 16, gap: 10 },
  infoTitle: { fontSize: 17, fontWeight: '800', color: '#0f172a' },
  infoText: { color: '#475569', lineHeight: 20 },
  helperText: { color: '#64748b', fontSize: 12, lineHeight: 18 },
  studentHeadRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 10 },
  studentIdentity: { flex: 1, gap: 2 },
  studentName: { fontSize: 14, color: '#475569', lineHeight: 19 },
  studentBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: '#eff6ff', borderWidth: 1, borderColor: '#bfdbfe' },
  studentBadgeText: { fontSize: 11, fontWeight: '700', color: '#1d4ed8' },
  statusBadgePill: { paddingHorizontal: 10, paddingVertical: 8, alignItems: 'center', justifyContent: 'center' },
  gradeGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  gradeField: { flex: 1, minWidth: '48%' },
  compactInput: { marginTop: 6 },
  notesInput: { minHeight: 72, textAlignVertical: 'top', marginTop: 6 },
  attendanceOptionRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  metricRow: { flexDirection: 'row', gap: 10 },
  metricPill: { flex: 1, backgroundColor: '#fff', borderRadius: 14, padding: 12, borderWidth: 1, borderColor: '#dbeafe' },
  metricPillLabel: { fontSize: 11, color: '#64748b', textTransform: 'uppercase', fontWeight: '700' },
  metricPillValue: { marginTop: 4, fontSize: 20, fontWeight: '800', color: '#1d4ed8' },
  searchInput: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 14, paddingHorizontal: 14, paddingVertical: 12, color: '#0f172a' },
  emptyCard: { backgroundColor: '#fff', borderRadius: 16, borderWidth: 1, borderColor: '#e2e8f0', padding: 18 },
  offerCard: { backgroundColor: '#fff', borderRadius: 18, borderWidth: 1, borderColor: '#dbeafe', padding: 16, gap: 12 },
  offerHeader: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  offerHeaderGrow: { flex: 1 },
  offerAction: { backgroundColor: '#dbeafe', borderRadius: 12, paddingHorizontal: 12, paddingVertical: 8 },
  offerActionText: { color: '#1d4ed8', fontWeight: '700' },
  offerMetaRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  matchCard: { backgroundColor: '#f8fafc', borderRadius: 14, borderWidth: 1, borderColor: '#e2e8f0', padding: 12, gap: 8 },
  offerTag: { backgroundColor: '#dcfce7', color: '#166534', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, overflow: 'hidden', fontSize: 12, fontWeight: '700' },
  offerTagSoft: { backgroundColor: '#f1f5f9', color: '#475569', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, overflow: 'hidden', fontSize: 12, fontWeight: '700' },
  statusOk: { backgroundColor: '#dcfce7', color: '#166534', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, overflow: 'hidden', fontSize: 12, fontWeight: '700' },
  statusPending: { backgroundColor: '#fee2e2', color: '#b91c1c', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, overflow: 'hidden', fontSize: 12, fontWeight: '700' },
  inlineOpenBtn: { alignSelf: 'flex-start', backgroundColor: '#dbeafe', borderRadius: 10, paddingHorizontal: 12, paddingVertical: 9 },
  inlineOpenBtnText: { color: '#1d4ed8', fontWeight: '700', fontSize: 13 },
  whatsappPanel: { marginTop: 4, paddingTop: 12, borderTopWidth: 1, borderTopColor: '#e2e8f0', gap: 8 },
  primaryBlockBtn: { marginTop: 8, backgroundColor: '#1d4ed8', borderRadius: 12, paddingVertical: 14, alignItems: 'center' },
  primaryBlockBtnDisabled: { backgroundColor: '#93c5fd' },
  primaryBlockBtnText: { color: '#fff', fontWeight: '800', fontSize: 14 },
  datePanel: { gap: 8 },
  inputNoMargin: { marginTop: 0 },
  calendarBtnText: { fontSize: 14, fontWeight: '700', color: '#1d4ed8' },
});

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8fafc' },
  center: { flex: 1, justifyContent: 'center', padding: 24, gap: 14 },
  header: { padding: 18, backgroundColor: '#fff', flexDirection: 'row', alignItems: 'center', gap: 12 },
  headerGrow: { flex: 1 },
  title: { fontSize: 21, fontWeight: '700', color: '#1e3a8a' },
  sub: { fontSize: 12, color: '#64748b' },
  list: { padding: 16, gap: 12 },
  card: { backgroundColor: '#fff', padding: 15, borderRadius: 12, borderWidth: 1, borderColor: '#e2e8f0', gap: 8 },
  cardTitle: { fontWeight: '700', color: '#111827', fontSize: 16 },
  muted: { color: '#64748b', marginTop: 6 },
  input: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 8, padding: 11, marginTop: 9 },
  section: { fontSize: 16, fontWeight: '700', marginTop: 8, color: '#1f2937' },
  filters: { paddingVertical: 4, gap: 8 },
  chip: { paddingHorizontal: 12, paddingVertical: 8, backgroundColor: '#e2e8f0', borderRadius: 16 },
  chipOn: { backgroundColor: '#93c5fd' },
  studentRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  studentRowGrow: { flex: 1 },
  badge: { fontSize: 12, fontWeight: '700', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 12, borderWidth: 1, overflow: 'hidden' },
  flag: { backgroundColor: '#fff', padding: 16, borderRadius: 12, borderWidth: 2, gap: 6 },
  flagLabel: { fontWeight: '700', color: '#111827', fontSize: 17 },
  flagCircle: { width: 28, height: 28, borderRadius: 14, borderWidth: 2, alignItems: 'center', justifyContent: 'center' },
  flagCheck: { fontSize: 15, fontWeight: '800' },
  loginScroll: { flexGrow: 1, justifyContent: 'center', padding: 24, backgroundColor: '#dbeafe' },
  loginWrap: { flex: 1, backgroundColor: '#dbeafe' },
  loginCard: { backgroundColor: '#fff', borderRadius: 18, padding: 22, gap: 14, borderWidth: 1, borderColor: '#bfdbfe' },
  logo: { width: 68, height: 68, borderRadius: 18, alignSelf: 'center', alignItems: 'center', justifyContent: 'center', backgroundColor: '#1d4ed8' },
  loginTitle: { textAlign: 'center', fontSize: 28, fontWeight: '800', color: '#0f172a' },
  loginSubtitle: { textAlign: 'center', fontSize: 17, fontWeight: '700', color: '#1d4ed8' },
  loginHint: { textAlign: 'center', color: '#475569', lineHeight: 20 },
  loginVersion: { textAlign: 'center', color: '#64748b', fontSize: 12, marginTop: -4 },
  fieldBlock: { gap: 4 },
  fieldLabel: { fontSize: 13, fontWeight: '700', color: '#334155' },
  passwordRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  passwordInput: { flex: 1, marginTop: 0 },
  eyeBtn: { paddingHorizontal: 12, paddingVertical: 11, borderRadius: 10, borderWidth: 1, borderColor: '#cbd5e1', backgroundColor: '#f8fafc', alignItems: 'center', justifyContent: 'center', minWidth: 72 },
  eyeText: { fontSize: 18 },
  eyeLabel: { marginTop: 2, fontSize: 11, color: '#475569', fontWeight: '600' },
  passwordHint: { color: '#64748b', fontSize: 12 },
  primaryBtn: { marginTop: 6, backgroundColor: '#1d4ed8', paddingVertical: 14, borderRadius: 12, alignItems: 'center' },
  primaryBtnDisabled: { backgroundColor: '#93c5fd' },
  primaryBtnText: { color: '#fff', fontWeight: '800', fontSize: 15 },
  sessionBox: { backgroundColor: '#f8fafc', borderRadius: 14, padding: 14, borderWidth: 1, borderColor: '#e2e8f0' },
  bioRow: { flexDirection: 'row', gap: 12, alignItems: 'center' },
  bioRowGrow: { flex: 1 },
  bioBtn: { backgroundColor: '#0f172a', paddingVertical: 14, borderRadius: 12, alignItems: 'center' },
  bioBtnText: { color: '#fff', fontWeight: '700' },
  pendingActions: { marginTop: 4, flexDirection: 'row', justifyContent: 'flex-start' },
  dateRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  dateInput: { flex: 1 },
  calendarBtn: { height: 46, paddingHorizontal: 16, borderRadius: 10, backgroundColor: '#dbeafe', justifyContent: 'center', alignItems: 'center' },
});
