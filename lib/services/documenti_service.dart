import 'package:dio/dio.dart';
import '../models/documento.dart';
import '../core/utils/app_exception.dart';

class DocumentiService {
  const DocumentiService(this._dio);

  final Dio _dio;

  Future<List<Documento>> getDocumenti({String? categoria}) async {
    try {
      final params = <String, dynamic>{};
      if (categoria != null) params['categoria'] = categoria;
      final response = await _dio.get('/documenti', queryParameters: params);
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?) ?? [];
      return list.map((e) => Documento.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }
}
