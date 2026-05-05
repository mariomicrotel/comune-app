import 'package:dio/dio.dart';
import '../models/luogo.dart';
import '../core/utils/app_exception.dart';

class LuoghiService {
  const LuoghiService(this._dio);

  final Dio _dio;

  Future<List<Luogo>> getLuoghi({String? categoria}) async {
    try {
      final params = <String, dynamic>{};
      if (categoria != null) params['categoria'] = categoria;
      final response = await _dio.get('/luoghi', queryParameters: params);
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?) ?? [];
      return list.map((e) => Luogo.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }
}
