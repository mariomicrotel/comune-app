class AppConfig {
  AppConfig._();

  static const String baseUrl = String.fromEnvironment(
    'BASE_URL',
    defaultValue: 'https://comune.example.it',
  );

  static const String comuneName = String.fromEnvironment(
    'COMUNE_NAME',
    defaultValue: 'Comune di Acerno',
  );

  static const String comuneProvince = String.fromEnvironment(
    'COMUNE_PROVINCE',
    defaultValue: 'Provincia di Salerno',
  );

  static const String comuneMotto = String.fromEnvironment(
    'COMUNE_MOTTO',
    defaultValue: 'Vivi il borgo',
  );

  static const int connectTimeoutMs = 10000;
  static const int receiveTimeoutMs = 15000;
  static const int maxRetries = 2;

  static const String apiBase = '$baseUrl/wp-json/comune/v1';
}
