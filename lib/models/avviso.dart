enum PrioritaAvviso { bassa, media, alta, urgente }

extension PrioritaAvvisoX on PrioritaAvviso {
  String get label {
    switch (this) {
      case PrioritaAvviso.urgente:
      case PrioritaAvviso.alta:
        return 'Urgente';
      case PrioritaAvviso.media:
        return 'Importante';
      case PrioritaAvviso.bassa:
        return 'Informazione';
    }
  }

  String get apiValue {
    switch (this) {
      case PrioritaAvviso.urgente:
        return 'urgente';
      case PrioritaAvviso.alta:
        return 'alta';
      case PrioritaAvviso.media:
        return 'media';
      case PrioritaAvviso.bassa:
        return 'bassa';
    }
  }

  static PrioritaAvviso fromString(String? s) {
    switch (s) {
      case 'urgente':
        return PrioritaAvviso.urgente;
      case 'alta':
        return PrioritaAvviso.alta;
      case 'media':
        return PrioritaAvviso.media;
      default:
        return PrioritaAvviso.bassa;
    }
  }
}

class Avviso {
  final int id;
  final String titolo;
  final String contenuto;
  final String? contenutoHtml;
  final DateTime data;
  final String categoria;
  final PrioritaAvviso priorita;
  final List<String> allegati;
  final bool featured;

  const Avviso({
    required this.id,
    required this.titolo,
    required this.contenuto,
    this.contenutoHtml,
    required this.data,
    required this.categoria,
    required this.priorita,
    required this.allegati,
    this.featured = false,
  });

  factory Avviso.fromJson(Map<String, dynamic> json) {
    return Avviso(
      id: json['id'] as int,
      titolo: json['titolo'] as String? ?? '',
      contenuto: json['contenuto'] as String? ?? '',
      contenutoHtml: json['content_html'] as String?,
      data: DateTime.tryParse(json['data'] as String? ?? '') ?? DateTime.now(),
      categoria: json['categoria'] as String? ?? '',
      priorita: PrioritaAvvisoX.fromString(json['priorita'] as String?),
      allegati: List<String>.from(json['allegati'] as List? ?? []),
      featured: (json['featured'] as bool?) ?? false,
    );
  }
}
