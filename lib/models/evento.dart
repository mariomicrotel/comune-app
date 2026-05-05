class Evento {
  final int id;
  final String titolo;
  final String descrizione;
  final String? descrizioneHtml;
  final String categoria;
  final DateTime dataInizio;
  final DateTime dataFine;
  final String luogo;
  final double? lat;
  final double? lng;
  final String? immagineUrl;
  final bool featured;

  const Evento({
    required this.id,
    required this.titolo,
    required this.descrizione,
    this.descrizioneHtml,
    required this.categoria,
    required this.dataInizio,
    required this.dataFine,
    required this.luogo,
    this.lat,
    this.lng,
    this.immagineUrl,
    this.featured = false,
  });

  factory Evento.fromJson(Map<String, dynamic> json) {
    return Evento(
      id: json['id'] as int,
      titolo: json['titolo'] as String? ?? '',
      descrizione: json['descrizione'] as String? ?? '',
      descrizioneHtml: json['content_html'] as String?,
      categoria: json['categoria'] as String? ?? '',
      dataInizio: DateTime.tryParse(json['data_inizio'] as String? ?? '') ??
          DateTime.now(),
      dataFine: DateTime.tryParse(json['data_fine'] as String? ?? '') ??
          DateTime.now(),
      luogo: json['luogo'] as String? ?? '',
      lat: (json['lat'] as num?)?.toDouble(),
      lng: (json['lng'] as num?)?.toDouble(),
      immagineUrl: json['immagine_url'] as String?,
      featured: (json['featured'] as bool?) ?? false,
    );
  }

  bool get isMultiDay {
    return dataInizio.year != dataFine.year ||
        dataInizio.month != dataFine.month ||
        dataInizio.day != dataFine.day;
  }
}
