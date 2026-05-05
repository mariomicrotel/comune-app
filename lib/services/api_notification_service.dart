import 'package:dio/dio.dart';
import '../core/utils/app_exception.dart';

class ApiNotificationService {
  const ApiNotificationService(this._dio);

  final Dio _dio;

  Future<void> registerToken({
    required String token,
    required String deviceId,
    required String platform,
  }) async {
    try {
      await _dio.post('/notifications/register', data: {
        'token': token,
        'device_id': deviceId,
        'platform': platform,
      });
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }

  Future<void> unregisterToken(String token) async {
    try {
      await _dio.delete('/notifications/unregister', data: {'token': token});
    } on DioException {
      // Silently fail on unregister
    }
  }
}
