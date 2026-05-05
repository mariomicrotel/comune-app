import 'package:dio/dio.dart';
import '../models/sondaggio.dart';
import '../core/utils/app_exception.dart';

class SondaggiService {
  const SondaggiService(this._dio);

  final Dio _dio;

  Future<List<Sondaggio>> getSondaggi() async {
    try {
      final response = await _dio.get('/sondaggi');
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?) ?? [];
      return list.map((e) => Sondaggio.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }

  Future<Sondaggio> getSondaggio(int id) async {
    try {
      final response = await _dio.get('/sondaggi/$id');
      final data = response.data as Map<String, dynamic>;
      return Sondaggio.fromJson(data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }

  Future<void> submitRisposte(int sondaggioId, Map<int, dynamic> risposte) async {
    try {
      await _dio.post(
        '/sondaggi/$sondaggioId/risposte',
        data: {
          'risposte': risposte.entries
              .map((e) => {'domanda_id': e.key, 'risposta': e.value})
              .toList(),
        },
      );
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }
}
