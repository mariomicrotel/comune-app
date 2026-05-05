import 'package:dio/dio.dart';
import '../models/ufficio.dart';
import '../core/utils/app_exception.dart';

class UfficiService {
  const UfficiService(this._dio);

  final Dio _dio;

  Future<List<Ufficio>> getUffici({String? categoria}) async {
    try {
      final params = <String, dynamic>{};
      if (categoria != null) params['categoria'] = categoria;
      final response = await _dio.get('/uffici', queryParameters: params);
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?) ?? [];
      return list.map((e) => Ufficio.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }

  Future<Ufficio> getUfficio(int id) async {
    try {
      final response = await _dio.get('/uffici/$id');
      final data = response.data as Map<String, dynamic>;
      return Ufficio.fromJson(data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }
}
