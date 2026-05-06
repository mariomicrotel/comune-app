import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/zona_rifiuti.dart';
import '../../core/providers/core_providers.dart';

final zoneRifiutiProvider =
    AsyncNotifierProvider<ZoneNotifier, List<ZonaRifiuti>>(ZoneNotifier.new);

class ZoneNotifier extends AsyncNotifier<List<ZonaRifiuti>> {
  @override
  Future<List<ZonaRifiuti>> build() =>
      ref.read(rifiutiServiceProvider).getZone();
}

final selectedZonaIdProvider =
    NotifierProvider<SelectedZonaNotifier, int?>(SelectedZonaNotifier.new);

class SelectedZonaNotifier extends Notifier<int?> {
  @override
  int? build() => ref.watch(preferencesServiceProvider).zonaRifiutiId;

  void set(int? value) => state = value;
}

final calendarioProvider =
    AsyncNotifierProvider<CalendarioNotifier, List<RaccoltaGiorno>>(
  CalendarioNotifier.new,
);

class CalendarioNotifier extends AsyncNotifier<List<RaccoltaGiorno>> {
  @override
  Future<List<RaccoltaGiorno>> build() {
    final zonaId = ref.watch(selectedZonaIdProvider);
    if (zonaId == null) return Future.value([]);

    final now = DateTime.now();
    final from = DateTime(now.year, now.month, now.day);
    final to = from.add(const Duration(days: 30));
    return ref
        .read(rifiutiServiceProvider)
        .getCalendario(zonaId, from: from, to: to);
  }
}

// Next collection from today
final prossimaRaccoltaProvider = Provider<RaccoltaGiorno?>((ref) {
  final cal = ref.watch(calendarioProvider);
  return cal.maybeWhen(
    data: (list) {
      final now = DateTime.now();
      final today = DateTime(now.year, now.month, now.day);
      try {
        return list.firstWhere((r) =>
            r.hasCollection &&
            !r.data.isBefore(today));
      } catch (_) {
        return null;
      }
    },
    orElse: () => null,
  );
});
