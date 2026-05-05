import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/luogo.dart';
import '../../core/providers/core_providers.dart';

final luoghiNotifierProvider =
    AsyncNotifierProvider<LuoghiNotifier, List<Luogo>>(LuoghiNotifier.new);

class LuoghiNotifier extends AsyncNotifier<List<Luogo>> {
  @override
  Future<List<Luogo>> build() =>
      ref.read(luoghiServiceProvider).getLuoghi();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(luoghiServiceProvider).getLuoghi(),
    );
  }
}
