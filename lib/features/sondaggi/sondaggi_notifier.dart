import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/sondaggio.dart';
import '../../core/providers/core_providers.dart';

final sondaggiNotifierProvider =
    AsyncNotifierProvider<SondaggiNotifier, List<Sondaggio>>(SondaggiNotifier.new);

class SondaggiNotifier extends AsyncNotifier<List<Sondaggio>> {
  @override
  Future<List<Sondaggio>> build() =>
      ref.read(sondaggiServiceProvider).getSondaggi();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(sondaggiServiceProvider).getSondaggi(),
    );
  }
}

// Single sondaggio detail
final sondaggioDetailProvider =
    FutureProvider.family<Sondaggio, int>((ref, id) =>
        ref.read(sondaggiServiceProvider).getSondaggio(id));

// Risposte form state
final risposteProvider =
    NotifierProvider<RisposteNotifier, Map<int, dynamic>>(
  RisposteNotifier.new,
);

class RisposteNotifier extends Notifier<Map<int, dynamic>> {
  @override
  Map<int, dynamic> build() => {};

  void set(int domandaId, dynamic value) =>
      state = {...state, domandaId: value};

  void toggleMultiple(int domandaId, String opzione) {
    final current = List<String>.from(state[domandaId] as List? ?? []);
    if (current.contains(opzione)) {
      current.remove(opzione);
    } else {
      current.add(opzione);
    }
    state = {...state, domandaId: current};
  }

  void reset() => state = {};
}
