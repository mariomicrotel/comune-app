import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import 'interceptors/auth_interceptor.dart';
import 'interceptors/error_interceptor.dart';
import 'interceptors/retry_interceptor.dart';

Dio createDio(SharedPreferences prefs) {
  final dio = Dio(
    BaseOptions(
      baseUrl: AppConfig.apiBase,
      connectTimeout: const Duration(milliseconds: AppConfig.connectTimeoutMs),
      receiveTimeout: const Duration(milliseconds: AppConfig.receiveTimeoutMs),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  dio.interceptors.addAll([
    AuthInterceptor(prefs),
    ErrorInterceptor(),
    RetryInterceptor(dio),
    if (const bool.fromEnvironment('dart.vm.product') == false)
      LogInterceptor(
        requestBody: true,
        responseBody: true,
        logPrint: (o) => print(o),
      ),
  ]);

  return dio;
}
