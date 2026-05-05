import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/avviso.dart';
import '../../core/providers/core_providers.dart';

final avvisiNotifierProvider =
    AsyncNotifierProvider<AvvisiNotifier, List<Avviso>>(AvvisiNotifier.new);

class AvvisiNotifier extends AsyncNotifier<List<Avviso>> {
  @override
  Future<List<Avviso>> build() =>
      ref.read(avvisiServiceProvider).getAvvisi();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(avvisiServiceProvider).getAvvisi(),
    );
  }
}

// Filtered provider — derived from the main list
final avvisiFilterProvider = StateProvider<String>((ref) => 'tutti');

final avvisiFiltrati = Provider<AsyncValue<List<Avviso>>>((ref) {
  final filter = ref.watch(avvisiFilterProvider);
  final all = ref.watch(avvisiNotifierProvider);
  return all.whenData((list) {
    switch (filter) {
      case 'urgenti':
        return list.where((a) =>
          a.priorita == PrioritaAvviso.urgente ||
          a.priorita == PrioritaAvviso.alta).toList();
      case 'importanti':
        return list.where((a) => a.priorita == PrioritaAvviso.media).toList();
      case 'info':
        return list.where((a) => a.priorita == PrioritaAvviso.bassa).toList();
      default:
        return list;
    }
  });
});

// Count helpers
final avvisiUrgentiCount = Provider<int>((ref) {
  return ref.watch(avvisiNotifierProvider).maybeWhen(
    data: (list) => list
        .where((a) =>
            a.priorita == PrioritaAvviso.urgente ||
            a.priorita == PrioritaAvviso.alta)
        .length,
    orElse: () => 0,
  );
});
