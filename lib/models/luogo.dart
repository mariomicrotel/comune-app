class Luogo {
  final int id;
  final String nome;
  final String categoria;
  final String descrizione;
  final String indirizzo;
  final double lat;
  final double lng;
  final String? immagineUrl;
  final String? orari;

  const Luogo({
    required this.id,
    required this.nome,
    required this.categoria,
    required this.descrizione,
    required this.indirizzo,
    required this.lat,
    required this.lng,
    this.immagineUrl,
    this.orari,
  });

  factory Luogo.fromJson(Map<String, dynamic> json) {
    return Luogo(
      id: json['id'] as int,
      nome: json['nome'] as String? ?? '',
      categoria: json['categoria'] as String? ?? '',
      descrizione: json['descrizione'] as String? ?? '',
      indirizzo: json['indirizzo'] as String? ?? '',
      lat: (json['lat'] as num?)?.toDouble() ?? 0.0,
      lng: (json['lng'] as num?)?.toDouble() ?? 0.0,
      immagineUrl: json['immagine_url'] as String?,
      orari: json['orari'] as String?,
    );
  }
}
