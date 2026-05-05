import 'package:dio/dio.dart';
import '../models/zona_rifiuti.dart';
import '../core/utils/app_exception.dart';

class RifiutiService {
  const RifiutiService(this._dio);

  final Dio _dio;

  Future<List<ZonaRifiuti>> getZone() async {
    try {
      final response = await _dio.get('/rifiuti/zone');
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?) ?? [];
      return list.map((e) => ZonaRifiuti.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }

  Future<List<RaccoltaGiorno>> getCalendario(int zonaId, {DateTime? from, DateTime? to}) async {
    try {
      final params = <String, dynamic>{'zona_id': zonaId};
      if (from != null) params['from'] = from.toIso8601String().substring(0, 10);
      if (to != null) params['to'] = to.toIso8601String().substring(0, 10);
      final response = await _dio.get('/rifiuti/calendario', queryParameters: params);
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?) ?? [];
      return list.map((e) => RaccoltaGiorno.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }
}
