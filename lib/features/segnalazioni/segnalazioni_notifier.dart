import 'dart:io';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/segnalazione.dart';
import '../../core/providers/core_providers.dart';

// My reports list
final mySegnalazioniProvider =
    AsyncNotifierProvider<MySegnalazioniNotifier, List<Segnalazione>>(
  MySegnalazioniNotifier.new,
);

class MySegnalazioniNotifier extends AsyncNotifier<List<Segnalazione>> {
  @override
  Future<List<Segnalazione>> build() {
    final deviceId =
        ref.read(preferencesServiceProvider).getOrCreateDeviceId();
    return ref.read(segnalazioniServiceProvider).getMie(deviceId);
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(build);
  }
}

// Create form state
class SegnalazioneFormState {
  final String? categoria;
  final String descrizione;
  final List<File> foto;
  final double? lat;
  final double? lng;
  final String? indirizzo;
  final bool locationDetected;

  const SegnalazioneFormState({
    this.categoria,
    this.descrizione = '',
    this.foto = const [],
    this.lat,
    this.lng,
    this.indirizzo,
    this.locationDetected = false,
  });

  SegnalazioneFormState copyWith({
    String? categoria,
    String? descrizione,
    List<File>? foto,
    double? lat,
    double? lng,
    String? indirizzo,
    bool? locationDetected,
  }) {
    return SegnalazioneFormState(
      categoria: categoria ?? this.categoria,
      descrizione: descrizione ?? this.descrizione,
      foto: foto ?? this.foto,
      lat: lat ?? this.lat,
      lng: lng ?? this.lng,
      indirizzo: indirizzo ?? this.indirizzo,
      locationDetected: locationDetected ?? this.locationDetected,
    );
  }

  bool get canSubmit => categoria != null;
}

final segnalazioneFormProvider =
    NotifierProvider<SegnalazioneFormNotifier, SegnalazioneFormState>(
  SegnalazioneFormNotifier.new,
);

class SegnalazioneFormNotifier extends Notifier<SegnalazioneFormState> {
  @override
  SegnalazioneFormState build() => const SegnalazioneFormState();

  void setCategoria(String c) => state = state.copyWith(categoria: c);
  void setDescrizione(String d) => state = state.copyWith(descrizione: d);
  void addFoto(File f) => state = state.copyWith(foto: [...state.foto, f]);
  void removeFoto(int i) {
    final list = [...state.foto]..removeAt(i);
    state = state.copyWith(foto: list);
  }

  void setLocation(double lat, double lng, String indirizzo) =>
      state = state.copyWith(
        lat: lat,
        lng: lng,
        indirizzo: indirizzo,
        locationDetected: true,
      );

  void reset() => state = const SegnalazioneFormState();
}
