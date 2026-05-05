class ZonaRifiuti {
  final int id;
  final String nome;
  final String slug;

  const ZonaRifiuti({
    required this.id,
    required this.nome,
    required this.slug,
  });

  factory ZonaRifiuti.fromJson(Map<String, dynamic> json) {
    return ZonaRifiuti(
      id: json['id'] as int,
      nome: json['nome'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
    );
  }
}

class RaccoltaGiorno {
  final DateTime data;
  final List<String> tipi; // e.g. ['organico', 'plastica']

  const RaccoltaGiorno({required this.data, required this.tipi});

  factory RaccoltaGiorno.fromJson(Map<String, dynamic> json) {
    return RaccoltaGiorno(
      data: DateTime.tryParse(json['data'] as String? ?? '') ?? DateTime.now(),
      tipi: List<String>.from(json['tipi'] as List? ?? []),
    );
  }

  bool get hasCollection => tipi.isNotEmpty;
}
