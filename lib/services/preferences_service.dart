import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

class PreferencesService {
  final SharedPreferences _prefs;

  PreferencesService(this._prefs);

  // Keys
  static const _kPrivacyConsent = 'privacy_consent';
  static const _kNotificationsConsent = 'notifications_consent';
  static const _kDeviceId = 'device_id';
  static const _kFcmToken = 'fcm_token';
  static const _kZonaRifiutiId = 'zona_rifiuti_id';
  static const _kThemeMode = 'theme_mode';
  static const _kPalette = 'palette';
  static const _kFontScale = 'font_scale';
  static const _kFirstName = 'first_name';

  bool get privacyConsent => _prefs.getBool(_kPrivacyConsent) ?? false;
  Future<void> setPrivacyConsent(bool v) => _prefs.setBool(_kPrivacyConsent, v);

  bool get notificationsConsent => _prefs.getBool(_kNotificationsConsent) ?? false;
  Future<void> setNotificationsConsent(bool v) =>
      _prefs.setBool(_kNotificationsConsent, v);

  String getOrCreateDeviceId() {
    final existing = _prefs.getString(_kDeviceId);
    if (existing != null) return existing;
    final id = const Uuid().v4();
    // Fire-and-forget is acceptable here — SharedPreferences writes to disk
    // asynchronously but maintains in-memory cache immediately
    _prefs.setString(_kDeviceId, id);
    return id;
  }

  String? get fcmToken => _prefs.getString(_kFcmToken);
  Future<void> setFcmToken(String token) => _prefs.setString(_kFcmToken, token);

  int? get zonaRifiutiId => _prefs.getInt(_kZonaRifiutiId);
  Future<void> setZonaRifiutiId(int id) => _prefs.setInt(_kZonaRifiutiId, id);

  String get themeMode => _prefs.getString(_kThemeMode) ?? 'system';
  Future<void> setThemeMode(String mode) => _prefs.setString(_kThemeMode, mode);

  String get palette => _prefs.getString(_kPalette) ?? 'bluCivico';
  Future<void> setPalette(String p) => _prefs.setString(_kPalette, p);

  String get fontScale => _prefs.getString(_kFontScale) ?? 'comfort';
  Future<void> setFontScale(String s) => _prefs.setString(_kFontScale, s);

  String? get firstName => _prefs.getString(_kFirstName);
  Future<void> setFirstName(String name) => _prefs.setString(_kFirstName, name);

  Future<void> clearAll() => _prefs.clear();
}
