import 'package:dio/dio.dart';
import '../../config/app_config.dart';

class RetryInterceptor extends Interceptor {
  final Dio _dio;

  RetryInterceptor(this._dio);

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    final options = err.requestOptions;
    final retryCount = options.extra['retryCount'] as int? ?? 0;

    final shouldRetry = retryCount < AppConfig.maxRetries &&
        (err.type == DioExceptionType.connectionTimeout ||
            err.type == DioExceptionType.receiveTimeout ||
            err.type == DioExceptionType.connectionError ||
            (err.response?.statusCode != null &&
                err.response!.statusCode! >= 500));

    if (!shouldRetry) {
      handler.next(err);
      return;
    }

    options.extra['retryCount'] = retryCount + 1;

    try {
      await Future.delayed(Duration(milliseconds: 500 * (retryCount + 1)));
      final response = await _dio.fetch(options);
      handler.resolve(response);
    } on DioException catch (e) {
      handler.next(e);
    }
  }
}
