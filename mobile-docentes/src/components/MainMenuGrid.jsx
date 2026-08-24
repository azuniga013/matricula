import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';

export default function MainMenuGrid({ modules, open }) {
  return (
    <View style={styles.grid}>
      {modules.map((item) => (
        <TouchableOpacity
          key={item.id}
          style={styles.card}
          onPress={() => open(item.id)}
          activeOpacity={0.85}
        >
          <View style={[styles.iconWrap, { backgroundColor: item.iconBg || '#dbeafe' }]}>
            <Ionicons name={item.icon || 'apps-outline'} size={24} color={item.iconColor || '#1d4ed8'} />
          </View>
          <Text style={styles.title}>{item.title}</Text>
          <Text style={styles.detail}>{item.detail}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  card: {
    width: '48%',
    minHeight: 150,
    backgroundColor: '#eff6ff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#bfdbfe',
    padding: 16,
    gap: 10,
    justifyContent: 'space-between',
  },
  iconWrap: {
    width: 48,
    height: 48,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    fontSize: 15,
    fontWeight: '700',
    color: '#0f172a',
  },
  detail: {
    fontSize: 12,
    lineHeight: 18,
    color: '#475569',
  },
});
