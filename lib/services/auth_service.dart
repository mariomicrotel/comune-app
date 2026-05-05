import 'preferences_service.dart';

// Stub for future SPID/CIE authentication.
// In v1, the app identifies users by their device UUID only.
class AuthService {
  final PreferencesService _prefs;

  AuthService(this._prefs);

  String getDeviceId() => _prefs.getOrCreateDeviceId();

  Future<void> logout() => _prefs.clearAll();
}
