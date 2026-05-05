import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/evento.dart';
import '../../core/providers/core_providers.dart';

final eventiNotifierProvider =
    AsyncNotifierProvider<EventiNotifier, List<Evento>>(EventiNotifier.new);

class EventiNotifier extends AsyncNotifier<List<Evento>> {
  @override
  Future<List<Evento>> build() =>
      ref.read(eventiServiceProvider).getEventi();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(eventiServiceProvider).getEventi(),
    );
  }
}
