import 'package:dio/dio.dart';
import '../../utils/app_exception.dart';

class ErrorInterceptor extends Interceptor {
  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    AppException appEx;

    switch (err.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.receiveTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.connectionError:
        appEx = const NetworkException();
      case DioExceptionType.badResponse:
        final statusCode = err.response?.statusCode;
        if (statusCode == 401 || statusCode == 403) {
          appEx = const UnauthorizedException();
        } else if (statusCode == 404) {
          appEx = const NotFoundException();
        } else {
          final msg = _extractMessage(err.response);
          appEx = ServerException(msg, statusCode);
        }
      default:
        if (err.error is AppException) {
          appEx = err.error as AppException;
        } else {
          appEx = UnknownException(err.message ?? 'Errore sconosciuto.');
        }
    }

    handler.reject(
      DioException(
        requestOptions: err.requestOptions,
        error: appEx,
        type: err.type,
        response: err.response,
      ),
    );
  }

  String _extractMessage(Response? response) {
    try {
      final data = response?.data;
      if (data is Map) {
        return (data['data']?['message'] ?? data['message'] ?? 'Errore del server.').toString();
      }
    } catch (_) {}
    return 'Errore del server.';
  }
}
