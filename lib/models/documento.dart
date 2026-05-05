class Documento {
  final int id;
  final String titolo;
  final String categoria;
  final String url;
  final DateTime data;
  final int? dimensioneKb;

  const Documento({
    required this.id,
    required this.titolo,
    required this.categoria,
    required this.url,
    required this.data,
    this.dimensioneKb,
  });

  factory Documento.fromJson(Map<String, dynamic> json) {
    return Documento(
      id: json['id'] as int,
      titolo: json['titolo'] as String? ?? '',
      categoria: json['categoria'] as String? ?? '',
      url: json['url'] as String? ?? '',
      data: DateTime.tryParse(json['data'] as String? ?? '') ?? DateTime.now(),
      dimensioneKb: json['dimensione_kb'] as int?,
    );
  }

  String get dimensioneLabel {
    if (dimensioneKb == null) return '';
    if (dimensioneKb! >= 1024) {
      return '${(dimensioneKb! / 1024).toStringAsFixed(1)} MB';
    }
    return '$dimensioneKb KB';
  }
}
