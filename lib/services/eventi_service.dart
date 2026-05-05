import 'package:dio/dio.dart';
import '../models/evento.dart';
import '../core/utils/app_exception.dart';

class EventiService {
  const EventiService(this._dio);

  final Dio _dio;

  Future<List<Evento>> getEventi({String? categoria}) async {
    try {
      final params = <String, dynamic>{};
      if (categoria != null) params['categoria'] = categoria;
      final response = await _dio.get('/eventi', queryParameters: params);
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?) ?? [];
      return list.map((e) => Evento.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }

  Future<Evento> getEvento(int id) async {
    try {
      final response = await _dio.get('/eventi/$id');
      final data = response.data as Map<String, dynamic>;
      return Evento.fromJson(data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }
}
