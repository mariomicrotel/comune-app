import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/documento.dart';
import '../../core/providers/core_providers.dart';

final documentiNotifierProvider =
    AsyncNotifierProvider<DocumentiNotifier, List<Documento>>(DocumentiNotifier.new);

class DocumentiNotifier extends AsyncNotifier<List<Documento>> {
  @override
  Future<List<Documento>> build() =>
      ref.read(documentiServiceProvider).getDocumenti();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(documentiServiceProvider).getDocumenti(),
    );
  }
}
