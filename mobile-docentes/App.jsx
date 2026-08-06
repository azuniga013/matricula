import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Alert, Button, FlatList, SafeAreaView, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import NetInfo from '@react-native-community/netinfo';
import { currentUser, grades, login, logout, offers, saveAttendance, saveGrades, students } from './src/api';
import { cachedGrades, cachedOffers, cachedStudents, clearLocalData, initDatabase, markError, pending, queue, removePending, replaceGrades, replaceOffers, replaceStudents } from './src/database';

const today = () => new Date().toISOString().slice(0, 10);
const fullName = (student) => `${student.nombre || ''} ${student.apellido || ''}`.trim();

export default function App() {
  const [user, setUser] = useState(null);
  const [online, setOnline] = useState(true);
  const [loading, setLoading] = useState(true);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [items, setItems] = useState([]);
  const [selected, setSelected] = useState(null);
  const [studentsForOffer, setStudentsForOffer] = useState([]);
  const [gradeRows, setGradeRows] = useState({});
  const [attendance, setAttendance] = useState({});
  const [date, setDate] = useState(today());
  const [syncing, setSyncing] = useState(false);

  useEffect(() => {
    initDatabase();
    setItems(cachedOffers());
    NetInfo.fetch().then((state) => setOnline(Boolean(state.isConnected)));
    const unsubscribe = NetInfo.addEventListener((state) => setOnline(Boolean(state.isConnected)));
    currentUser().then((profile) => {
      if (!profile.docente_id) throw new Error('Esta cuenta no está vinculada a un docente');
      setUser(profile);
    }).catch(() => {}).finally(() => setLoading(false));
    return unsubscribe;
  }, []);

  const pendingCount = useMemo(() => pending().length, [items, selected, attendance, gradeRows, syncing]);

  async function refresh() {
    if (!online) return Alert.alert('Sin conexión', 'Se muestran los datos descargados anteriormente.');
    setSyncing(true);
    try {
      const downloadedOffers = await offers();
      replaceOffers(downloadedOffers);
      for (const offer of downloadedOffers) {
        const [enrolled, gradeData] = await Promise.all([students(offer.id), grades(offer.id)]);
        replaceStudents(offer.id, enrolled);
        replaceGrades(offer.id, gradeData);
      }
      setItems(cachedOffers());
      if (selected) await openOffer(selected.id);
    } catch (error) {
      Alert.alert('No se pudo sincronizar', error.message);
    } finally { setSyncing(false); }
  }

  async function synchronizeQueue() {
    if (!online) return Alert.alert('Sin conexión', 'La cola se enviará cuando recupere internet.');
    setSyncing(true);
    try {
      for (const operation of pending()) {
        const data = JSON.parse(operation.datos);
        try {
          if (operation.tipo === 'asistencia') await saveAttendance(operation.oferta_id, data.fecha, data.asistencias);
          if (operation.tipo === 'calificaciones') await saveGrades(operation.oferta_id, data.calificaciones);
          removePending(operation.uuid);
        } catch (error) {
          markError(operation.uuid, error.message);
          if (error.status === 401 || error.status === 403 || error.status === 422) break;
        }
      }
      await refresh();
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
      } catch (_) { /* conservar copia local */ }
    }
  }

  async function saveAttendanceLocally() {
    const asistencias = studentsForOffer.map((student) => ({
      matricula_id: student.matricula_id,
      estado: attendance[student.matricula_id]?.estado || 'presente',
      observacion: attendance[student.matricula_id]?.observacion || null,
    }));
    queue('asistencia', selected.id, { fecha: date, asistencias });
    Alert.alert('Guardado local', 'La asistencia queda pendiente de sincronización.');
    if (online) await synchronizeQueue();
  }

  async function saveGradesLocally() {
    const calificaciones = studentsForOffer.map((student) => ({
      estudiante_id: student.estudiante_id || student.id,
      nota_final: gradeRows[student.estudiante_id || student.id]?.nota_final || null,
      faltas: Number(gradeRows[student.estudiante_id || student.id]?.faltas || 0),
      observaciones: gradeRows[student.estudiante_id || student.id]?.observaciones || null,
    }));
    queue('calificaciones', selected.id, { calificaciones });
    Alert.alert('Guardado local', 'Las notas quedan pendientes de sincronización.');
    if (online) await synchronizeQueue();
  }

  async function submitLogin() {
    setLoading(true);
    try {
      const profile = await login(email.trim(), password);
      setUser(profile); await refresh();
    } catch (error) { Alert.alert('No se pudo iniciar sesión', error.message); }
    finally { setLoading(false); }
  }

  async function closeSession() {
    await logout(); clearLocalData(); setUser(null); setSelected(null); setItems([]);
  }

  if (loading) return <Centered text="Preparando aplicación docente..." />;
  if (!user) return <Login email={email} setEmail={setEmail} password={password} setPassword={setPassword} submit={submitLogin} />;
  if (selected) return <OfferDetail offer={selected} students={studentsForOffer} attendance={attendance} setAttendance={setAttendance} grades={gradeRows} setGrades={setGradeRows} date={date} setDate={setDate} online={online} saveAttendance={saveAttendanceLocally} saveGrades={saveGradesLocally} back={() => setSelected(null)} />;

  return <SafeAreaView style={styles.container}><View style={styles.header}><View><Text style={styles.title}>Mis ofertas</Text><Text style={styles.sub}>{user.nombre || user.name} · {online ? 'En línea' : 'Sin conexión'} · {pendingCount} pendientes</Text></View><Button title={syncing ? 'Sincronizando' : 'Sincronizar'} disabled={syncing} onPress={synchronizeQueue} /></View><FlatList data={items} keyExtractor={(item) => String(item.id)} contentContainerStyle={styles.list} ListEmptyComponent={<Text style={styles.muted}>No hay ofertas descargadas. Conéctese y presione Sincronizar.</Text>} renderItem={({ item }) => <TouchableOpacity style={styles.card} onPress={() => openOffer(item.id)}><Text style={styles.cardTitle}>{item.codigo} · {item.nivel_academico?.nombre}</Text><Text style={styles.muted}>{item.periodo_academico?.nombre} · {item.horario?.nombre}</Text></TouchableOpacity>} /><View style={styles.footer}><Button title="Cerrar sesión" onPress={closeSession} /></View></SafeAreaView>;
}

function Login({ email, setEmail, password, setPassword, submit }) { return <SafeAreaView style={styles.center}><Text style={styles.title}>Cursos SVP · Docentes</Text><Text style={styles.muted}>Use su usuario administrativo vinculado como docente.</Text><TextInput style={styles.input} placeholder="Correo" autoCapitalize="none" keyboardType="email-address" value={email} onChangeText={setEmail} /><TextInput style={styles.input} placeholder="Contraseña" secureTextEntry value={password} onChangeText={setPassword} /><Button title="Iniciar sesión" onPress={submit} /></SafeAreaView>; }
function Centered({ text }) { return <View style={styles.center}><ActivityIndicator size="large" /><Text style={styles.muted}>{text}</Text></View>; }
function OfferDetail({ offer, students, attendance, setAttendance, grades, setGrades, date, setDate, online, saveAttendance, saveGrades, back }) { return <SafeAreaView style={styles.container}><View style={styles.header}><Button title="Volver" onPress={back} /><View><Text style={styles.title}>{offer.nivel_academico?.nombre}</Text><Text style={styles.muted}>{online ? 'En línea' : 'Modo offline'}</Text></View></View><ScrollView contentContainerStyle={styles.list}><Text style={styles.section}>Asistencia · fecha YYYY-MM-DD</Text><TextInput style={styles.input} value={date} onChangeText={setDate} /><Button title="Guardar asistencia local" onPress={saveAttendance} /><Text style={styles.section}>Alumnos y calificaciones</Text>{students.map((student) => { const id = student.estudiante_id || student.id; const row = grades[id] || {}; return <View key={student.matricula_id} style={styles.card}><Text style={styles.cardTitle}>{student.codigo} · {fullName(student)}</Text><View style={styles.actions}>{['presente','falta','justificada','tardanza'].map((state) => <TouchableOpacity key={state} style={[styles.pill, attendance[student.matricula_id]?.estado === state && styles.pillOn]} onPress={() => setAttendance({ ...attendance, [student.matricula_id]: { ...(attendance[student.matricula_id] || {}), estado: state } })}><Text>{state}</Text></TouchableOpacity>)}</View><TextInput style={styles.input} placeholder="Nota final" keyboardType="decimal-pad" value={String(row.nota_final ?? '')} onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, nota_final: value } })} /><TextInput style={styles.input} placeholder="Faltas" keyboardType="number-pad" value={String(row.faltas ?? 0)} onChangeText={(value) => setGrades({ ...grades, [id]: { ...row, faltas: value } })} /></View>; })}<Button title="Guardar notas localmente" onPress={saveGrades} /></ScrollView></SafeAreaView>; }

const styles = StyleSheet.create({ container:{flex:1,backgroundColor:'#f8fafc'}, center:{flex:1,justifyContent:'center',padding:24,gap:14}, header:{padding:18,backgroundColor:'#fff',flexDirection:'row',justifyContent:'space-between',alignItems:'center',gap:12}, title:{fontSize:21,fontWeight:'700',color:'#1e3a8a'}, sub:{fontSize:12,color:'#64748b'}, list:{padding:16,gap:12}, card:{backgroundColor:'#fff',padding:15,borderRadius:10,borderWidth:1,borderColor:'#e2e8f0',gap:8}, cardTitle:{fontWeight:'700',color:'#111827'}, muted:{color:'#64748b',marginTop:6}, input:{backgroundColor:'#fff',borderWidth:1,borderColor:'#cbd5e1',borderRadius:8,padding:11,marginTop:9}, section:{fontSize:16,fontWeight:'700',marginTop:8,color:'#1f2937'}, actions:{flexDirection:'row',flexWrap:'wrap',gap:6}, pill:{paddingHorizontal:8,paddingVertical:5,borderRadius:14,backgroundColor:'#e2e8f0'}, pillOn:{backgroundColor:'#86efac'}, footer:{padding:16} });
