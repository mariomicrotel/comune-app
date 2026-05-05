import 'dart:io';
import 'package:dio/dio.dart';
import '../models/segnalazione.dart';
import '../core/utils/app_exception.dart';

class SegnalazioniService {
  const SegnalazioniService(this._dio);

  final Dio _dio;

  Future<List<Segnalazione>> getMie(String deviceId) async {
    try {
      final response = await _dio.get(
        '/segnalazioni',
        queryParameters: {'device_id': deviceId},
      );
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?) ?? [];
      return list.map((e) => Segnalazione.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }

  Future<Segnalazione> getSegnalazione(String publicCode) async {
    try {
      final response = await _dio.get('/segnalazioni/$publicCode');
      final data = response.data as Map<String, dynamic>;
      return Segnalazione.fromJson(data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }

  Future<Segnalazione> submit({
    required String categoria,
    required String descrizione,
    required String deviceId,
    double? lat,
    double? lng,
    List<File>? foto,
  }) async {
    try {
      final formData = FormData.fromMap({
        'categoria': categoria,
        'descrizione': descrizione,
        'device_id': deviceId,
        if (lat != null) 'lat': lat.toString(),
        if (lng != null) 'lng': lng.toString(),
        if (foto != null)
          for (int i = 0; i < foto.length; i++)
            'foto[]': await MultipartFile.fromFile(foto[i].path),
      });

      final response = await _dio.post(
        '/segnalazioni',
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );
      final data = response.data as Map<String, dynamic>;
      return Segnalazione.fromJson(data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw (e.error as AppException?) ?? UnknownException(e.message ?? '');
    }
  }
}
