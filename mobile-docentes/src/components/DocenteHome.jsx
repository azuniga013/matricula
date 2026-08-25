import { Ionicons } from '@expo/vector-icons';
import { ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import MainMenuGrid from './MainMenuGrid';

function StatCard({ label, value, hint, tone = 'blue' }) {
  const tones = {
    blue: { bg: '#eff6ff', border: '#bfdbfe', value: '#1d4ed8' },
    green: { bg: '#ecfdf5', border: '#a7f3d0', value: '#047857' },
    amber: { bg: '#fffbeb', border: '#fde68a', value: '#b45309' },
    rose: { bg: '#fff1f2', border: '#fecdd3', value: '#be123c' },
  };
  const palette = tones[tone] || tones.blue;

  return (
    <View style={[styles.statCard, { backgroundColor: palette.bg, borderColor: palette.border }]}> 
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={[styles.statValue, { color: palette.value }]}>{value}</Text>
      <Text style={styles.statHint}>{hint}</Text>
    </View>
  );
}

export default function DocenteHome({
  user,
  online,
  pendingCount,
  syncSummary,
  recentErrors,
  modules,
  open,
  sync,
  syncing,
  logout,
  dashboard,
}) {
  const docenteNombre = user?.nombre || user?.name || 'Docente';
  const docenteCorreo = user?.email || 'Sin correo registrado';
  const docenteRol = user?.roles?.[0]?.nombre || user?.roles?.[0]?.codigo || 'Sin rol asignado';
  const docenteSucursales = (user?.sucursales || []).map((item) => item?.nombre || item?.codigo).filter(Boolean).join(' · ') || 'Sin sucursales asignadas';

  return (
    <View style={styles.container}>
      <View style={styles.hero}>
        <View style={styles.heroTopRow}>
          <View style={styles.avatar}>
            <Ionicons name="school-outline" size={28} color="#fff" />
          </View>
          <View style={styles.heroGrow}>
            <Text style={styles.heroTitle}>Portal Docente</Text>
            <Text style={styles.heroName}>{docenteNombre}</Text>
            <Text style={styles.heroEmail}>{docenteCorreo}</Text>
            <Text style={styles.heroMeta}>{online ? 'En linea' : 'Modo offline'} · {pendingCount} pendiente(s)</Text>
          </View>
        </View>
        <View style={styles.heroActions}>
          <TouchableOpacity style={styles.primaryAction} onPress={sync} disabled={syncing} activeOpacity={0.85}>
            <Ionicons name="sync-outline" size={18} color="#1e3a8a" />
            <Text style={styles.primaryActionText}>{syncing ? 'Sincronizando...' : 'Actualizar datos'}</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.secondaryAction} onPress={logout} activeOpacity={0.85}>
            <Ionicons name="log-out-outline" size={18} color="#334155" />
            <Text style={styles.secondaryActionText}>Cerrar sesion</Text>
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.profileCard}>
          <View style={styles.profileRow}>
            <View style={styles.profileIconWrap}>
              <Ionicons name="person-circle-outline" size={24} color="#1d4ed8" />
            </View>
            <View style={styles.profileGrow}>
              <Text style={styles.profileLabel}>Docente</Text>
              <Text style={styles.profileName}>{docenteNombre}</Text>
              <Text style={styles.profileEmail}>{docenteCorreo}</Text>
            </View>
          </View>
          <View style={styles.profileMetaGrid}>
            <View style={styles.profileMetaItem}>
              <Text style={styles.profileMetaLabel}>Rol</Text>
              <Text style={styles.profileMetaValue}>{docenteRol}</Text>
            </View>
            <View style={styles.profileMetaItem}>
              <Text style={styles.profileMetaLabel}>Sucursales</Text>
              <Text style={styles.profileMetaValue}>{docenteSucursales}</Text>
            </View>
          </View>
        </View>

        <View style={styles.sectionHead}>
          <Text style={styles.sectionTitle}>Resumen rapido</Text>
          <Text style={styles.sectionText}>Acceso directo a su jornada y a los modulos principales.</Text>
        </View>

        <View style={styles.statsGrid}>
          <StatCard label="Horarios" value={dashboard.offersCount} hint="Ofertas descargadas" tone="blue" />
          <StatCard label="Estudiantes" value={dashboard.studentsCount} hint="Registros disponibles" tone="green" />
          <StatCard label="Periodos" value={dashboard.periodsCount} hint={dashboard.primaryPeriodLabel || 'Sin periodo filtrado'} tone="amber" />
          <StatCard label="Pendientes" value={pendingCount} hint={pendingCount ? 'Requieren sincronizacion' : 'Todo al dia'} tone={pendingCount ? 'rose' : 'green'} />
        </View>

        <View style={styles.panel}>
          <Text style={styles.panelTitle}>Estado actual</Text>
          <Text style={styles.panelText}>{syncSummary?.message || (online ? 'Puede actualizar su informacion antes de comenzar.' : 'Trabaja con la ultima informacion descargada en este dispositivo.')}</Text>
          {recentErrors.length ? (
            <View style={styles.errorBox}>
              <Text style={styles.errorTitle}>Pendientes con error</Text>
              {recentErrors.map((item) => (
                <Text key={item.uuid} style={styles.errorLine}>• {item.tipo}: {item.ultimo_error}</Text>
              ))}
            </View>
          ) : null}
        </View>

        <View style={styles.sectionHead}>
          <Text style={styles.sectionTitle}>Modulos</Text>
          <Text style={styles.sectionText}>Cada acceso incluye una descripcion breve para entrar mas rapido.</Text>
        </View>
        <MainMenuGrid modules={modules} open={open} />

        {!online && pendingCount > 0 ? (
          <View style={styles.panelMuted}>
            <Text style={styles.panelTitle}>Trabajo offline</Text>
            <Text style={styles.panelText}>Tiene cambios locales guardados. Cuando recupere internet, entre a Sincronizacion para reenviar o revisar conflictos.</Text>
          </View>
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8fafc' },
  hero: {
    backgroundColor: '#1e3a8a',
    paddingHorizontal: 18,
    paddingTop: 22,
    paddingBottom: 18,
    gap: 16,
  },
  heroTopRow: { flexDirection: 'row', gap: 14, alignItems: 'center' },
  avatar: {
    width: 58,
    height: 58,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroGrow: { flex: 1 },
  heroTitle: { color: '#bfdbfe', fontSize: 13, fontWeight: '600' },
  heroName: { color: '#fff', fontSize: 22, fontWeight: '800', marginTop: 2 },
  heroEmail: { color: '#dbeafe', fontSize: 13, marginTop: 4 },
  heroMeta: { color: '#dbeafe', fontSize: 12, marginTop: 4 },
  heroActions: { flexDirection: 'row', gap: 10 },
  primaryAction: {
    flex: 1,
    backgroundColor: '#fff',
    borderRadius: 14,
    paddingVertical: 12,
    paddingHorizontal: 14,
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryActionText: { color: '#1e3a8a', fontWeight: '700' },
  secondaryAction: {
    backgroundColor: 'rgba(255,255,255,0.16)',
    borderRadius: 14,
    paddingVertical: 12,
    paddingHorizontal: 14,
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryActionText: { color: '#fff', fontWeight: '600' },
  scroll: { padding: 16, gap: 14 },
  profileCard: {
    backgroundColor: '#fff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#dbeafe',
    padding: 16,
  },
  profileRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  profileIconWrap: {
    width: 46,
    height: 46,
    borderRadius: 14,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileGrow: { flex: 1 },
  profileLabel: { fontSize: 12, fontWeight: '700', color: '#64748b', textTransform: 'uppercase' },
  profileName: { fontSize: 18, fontWeight: '800', color: '#0f172a', marginTop: 2 },
  profileEmail: { fontSize: 13, color: '#475569', marginTop: 4 },
  profileMetaGrid: { marginTop: 14, gap: 10 },
  profileMetaItem: { backgroundColor: '#f8fafc', borderRadius: 14, borderWidth: 1, borderColor: '#e2e8f0', padding: 12 },
  profileMetaLabel: { fontSize: 11, fontWeight: '700', color: '#64748b', textTransform: 'uppercase' },
  profileMetaValue: { fontSize: 14, fontWeight: '700', color: '#0f172a', marginTop: 4 },
  sectionHead: { gap: 4 },
  sectionTitle: { fontSize: 18, fontWeight: '800', color: '#0f172a' },
  sectionText: { fontSize: 13, color: '#64748b' },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  statCard: {
    width: '48%',
    borderRadius: 18,
    borderWidth: 1,
    padding: 14,
    gap: 6,
  },
  statLabel: { fontSize: 12, fontWeight: '700', color: '#334155', textTransform: 'uppercase' },
  statValue: { fontSize: 28, fontWeight: '800' },
  statHint: { fontSize: 12, color: '#64748b', lineHeight: 18 },
  panel: {
    backgroundColor: '#fff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    padding: 16,
    gap: 10,
  },
  panelMuted: {
    backgroundColor: '#f1f5f9',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#cbd5e1',
    padding: 16,
    gap: 8,
  },
  panelTitle: { fontSize: 16, fontWeight: '800', color: '#0f172a' },
  panelText: { fontSize: 13, color: '#475569', lineHeight: 19 },
  errorBox: {
    marginTop: 4,
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
    borderWidth: 1,
    borderRadius: 14,
    padding: 12,
    gap: 6,
  },
  errorTitle: { fontWeight: '700', color: '#9a3412' },
  errorLine: { color: '#9a3412', fontSize: 12, lineHeight: 17 },
});
