import 'dart:convert';
import 'package:dio/dio.dart';
import 'preferences_service.dart';

/// Servizio di autenticazione.
///
/// In v1 l'utente è identificato dal deviceId (UUID locale).
/// L'architettura è predisposta per futura integrazione SPID/CIE
/// senza modificare il layer delle feature.
class AuthService {
  final PreferencesService _prefs;
  final Dio _dio;

  AuthService(this._prefs, this._dio);

  // ── Identità ──────────────────────────────────────────────────────────────

  String getDeviceId() => _prefs.getOrCreateDeviceId();

  String get displayName => _prefs.displayName;

  bool get isRegistered => _prefs.onboardingComplete;

  // ── GDPR: Data export (Art. 20) ───────────────────────────────────────────

  /// Esporta tutti i dati personali in formato JSON (portabilità).
  String exportUserDataJson() {
    final data = _prefs.exportUserData();
    const encoder = JsonEncoder.withIndent('  ');
    return encoder.convert(data);
  }

  /// Esporta i dati personali come Map per uso interno.
  Map<String, dynamic> exportUserData() => _prefs.exportUserData();

  // ── GDPR: Data deletion request (Art. 17) ─────────────────────────────────

  /// Richiede la cancellazione dei dati personali.
  /// Cancella i dati locali e invia la richiesta al server.
  Future<void> requestDataDeletion() async {
    final deviceId = getDeviceId();

    // Notify server of deletion request (best-effort)
    try {
      await _dio.post('/dispositivi/$deviceId/cancella-dati');
    } catch (_) {
      // Server unreachable — the local deletion still proceeds.
      // The server should also have a TTL-based cleanup.
    }

    // Clear local personal data (preserves deviceId for server tracking)
    await _prefs.clearPersonalData();
  }

  // ── Logout / Reset ────────────────────────────────────────────────────────

  /// Reset completo: cancella tutti i dati locali e torna all'onboarding.
  Future<void> logout() async {
    await _prefs.clearAll();
  }

  // ── Future: SPID / CIE ────────────────────────────────────────────────────
  // Future<void> loginWithSpid() async { ... }
  // Future<void> loginWithCie() async { ... }
}
