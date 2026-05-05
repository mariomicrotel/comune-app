import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/ufficio.dart';
import '../../core/providers/core_providers.dart';

final ufficiNotifierProvider =
    AsyncNotifierProvider<UfficiNotifier, List<Ufficio>>(UfficiNotifier.new);

class UfficiNotifier extends AsyncNotifier<List<Ufficio>> {
  @override
  Future<List<Ufficio>> build() =>
      ref.read(ufficiServiceProvider).getUffici();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(ufficiServiceProvider).getUffici(),
    );
  }
}
