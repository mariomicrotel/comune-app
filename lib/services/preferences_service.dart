import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

class PreferencesService {
  final SharedPreferences _prefs;

  PreferencesService(this._prefs);

  // ── Keys ────────────────────────────────────────────────────────────────────
  static const _kPrivacyConsent = 'privacy_consent';
  static const _kPrivacyConsentDate = 'privacy_consent_date';
  static const _kNotificationsConsent = 'notifications_consent';
  static const _kAnalyticsConsent = 'analytics_consent';
  static const _kLocationConsent = 'location_consent';
  static const _kDeviceId = 'device_id';
  static const _kFcmToken = 'fcm_token';
  static const _kZonaRifiutiId = 'zona_rifiuti_id';
  static const _kThemeMode = 'theme_mode';
  static const _kPalette = 'palette';
  static const _kFontScale = 'font_scale';
  static const _kFirstName = 'first_name';
  static const _kLastName = 'last_name';
  static const _kEmail = 'email';
  static const _kOnboardingComplete = 'onboarding_complete';
  static const _kDataDeletionRequested = 'data_deletion_requested';

  // ── Privacy & Consensi (GDPR) ───────────────────────────────────────────────

  /// Consenso obbligatorio al trattamento dati (Art. 6 GDPR).
  bool get privacyConsent => _prefs.getBool(_kPrivacyConsent) ?? false;
  Future<void> setPrivacyConsent(bool v) async {
    await _prefs.setBool(_kPrivacyConsent, v);
    if (v) {
      await _prefs.setString(
          _kPrivacyConsentDate, DateTime.now().toIso8601String());
    } else {
      await _prefs.remove(_kPrivacyConsentDate);
    }
  }

  /// Data del consenso privacy (per audit trail).
  String? get privacyConsentDate => _prefs.getString(_kPrivacyConsentDate);

  /// Consenso notifiche push (opzionale, revocabile).
  bool get notificationsConsent =>
      _prefs.getBool(_kNotificationsConsent) ?? false;
  Future<void> setNotificationsConsent(bool v) =>
      _prefs.setBool(_kNotificationsConsent, v);

  /// Consenso analytics/statistiche anonime (opzionale, revocabile).
  bool get analyticsConsent => _prefs.getBool(_kAnalyticsConsent) ?? false;
  Future<void> setAnalyticsConsent(bool v) =>
      _prefs.setBool(_kAnalyticsConsent, v);

  /// Consenso geolocalizzazione per segnalazioni (opzionale, revocabile).
  bool get locationConsent => _prefs.getBool(_kLocationConsent) ?? false;
  Future<void> setLocationConsent(bool v) =>
      _prefs.setBool(_kLocationConsent, v);

  /// Onboarding completato (tutti gli step superati).
  bool get onboardingComplete =>
      _prefs.getBool(_kOnboardingComplete) ?? false;
  Future<void> setOnboardingComplete(bool v) =>
      _prefs.setBool(_kOnboardingComplete, v);

  // ── Identità dispositivo ───────────────────────────────────────────────────

  String getOrCreateDeviceId() {
    final existing = _prefs.getString(_kDeviceId);
    if (existing != null) return existing;
    final id = const Uuid().v4();
    _prefs.setString(_kDeviceId, id);
    return id;
  }

  // ── Dati utente ─────────────────────────────────────────────────────────────

  String? get firstName => _prefs.getString(_kFirstName);
  Future<void> setFirstName(String name) =>
      _prefs.setString(_kFirstName, name);

  String? get lastName => _prefs.getString(_kLastName);
  Future<void> setLastName(String name) =>
      _prefs.setString(_kLastName, name);

  String? get email => _prefs.getString(_kEmail);
  Future<void> setEmail(String email) =>
      _prefs.setString(_kEmail, email);

  String get displayName {
    final first = firstName;
    final last = lastName;
    if (first != null && last != null) return '$first $last';
    if (first != null) return first;
    return 'Cittadino';
  }

  // ── FCM ─────────────────────────────────────────────────────────────────────

  String? get fcmToken => _prefs.getString(_kFcmToken);
  Future<void> setFcmToken(String token) =>
      _prefs.setString(_kFcmToken, token);

  // ── Preferenze app ──────────────────────────────────────────────────────────

  int? get zonaRifiutiId => _prefs.getInt(_kZonaRifiutiId);
  Future<void> setZonaRifiutiId(int id) => _prefs.setInt(_kZonaRifiutiId, id);

  String get themeMode => _prefs.getString(_kThemeMode) ?? 'system';
  Future<void> setThemeMode(String mode) =>
      _prefs.setString(_kThemeMode, mode);

  String get palette => _prefs.getString(_kPalette) ?? 'bluCivico';
  Future<void> setPalette(String p) => _prefs.setString(_kPalette, p);

  String get fontScale => _prefs.getString(_kFontScale) ?? 'comfort';
  Future<void> setFontScale(String s) => _prefs.setString(_kFontScale, s);

  // ── GDPR: cancellazione e export dati ───────────────────────────────────────

  bool get dataDeletionRequested =>
      _prefs.getBool(_kDataDeletionRequested) ?? false;
  Future<void> setDataDeletionRequested(bool v) =>
      _prefs.setBool(_kDataDeletionRequested, v);

  /// Raccoglie tutti i dati personali dell'utente per export (Art. 20 GDPR).
  Map<String, dynamic> exportUserData() {
    return {
      'device_id': _prefs.getString(_kDeviceId),
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'privacy_consent': privacyConsent,
      'privacy_consent_date': privacyConsentDate,
      'notifications_consent': notificationsConsent,
      'analytics_consent': analyticsConsent,
      'location_consent': locationConsent,
      'zona_rifiuti_id': zonaRifiutiId,
      'theme_mode': themeMode,
      'palette': palette,
      'font_scale': fontScale,
      'exported_at': DateTime.now().toIso8601String(),
    };
  }

  /// Cancella tutti i dati personali mantenendo il device_id
  /// per poter tracciare la richiesta di cancellazione lato server.
  Future<void> clearPersonalData() async {
    final deviceId = getOrCreateDeviceId();
    await _prefs.clear();
    // Mantieni device_id per la richiesta di cancellazione server-side
    await _prefs.setString(_kDeviceId, deviceId);
    await _prefs.setBool(_kDataDeletionRequested, true);
  }

  /// Cancella tutto — reset completo.
  Future<void> clearAll() => _prefs.clear();
}
