enum TipoDomanda { sceltaSingola, sceltaMultipla, testo }

extension TipoDomandaX on TipoDomanda {
  static TipoDomanda fromString(String? s) {
    switch (s) {
      case 'scelta_multipla':
        return TipoDomanda.sceltaMultipla;
      case 'testo':
        return TipoDomanda.testo;
      default:
        return TipoDomanda.sceltaSingola;
    }
  }
}

class Domanda {
  final int id;
  final String testo;
  final TipoDomanda tipo;
  final List<String> opzioni;

  const Domanda({
    required this.id,
    required this.testo,
    required this.tipo,
    required this.opzioni,
  });

  factory Domanda.fromJson(Map<String, dynamic> json) {
    return Domanda(
      id: json['id'] as int,
      testo: json['testo'] as String? ?? '',
      tipo: TipoDomandaX.fromString(json['tipo'] as String?),
      opzioni: List<String>.from(json['opzioni'] as List? ?? []),
    );
  }
}

class Sondaggio {
  final int id;
  final String titolo;
  final String descrizione;
  final bool aperto;
  final int partecipanti;
  final List<Domanda> domande;
  final DateTime? scadenza;

  const Sondaggio({
    required this.id,
    required this.titolo,
    required this.descrizione,
    required this.aperto,
    required this.partecipanti,
    required this.domande,
    this.scadenza,
  });

  factory Sondaggio.fromJson(Map<String, dynamic> json) {
    final domList = (json['domande'] as List?) ?? [];
    return Sondaggio(
      id: json['id'] as int,
      titolo: json['titolo'] as String? ?? '',
      descrizione: json['descrizione'] as String? ?? '',
      aperto: (json['aperto'] as bool?) ?? false,
      partecipanti: (json['partecipanti'] as int?) ?? 0,
      domande: domList.map((e) => Domanda.fromJson(e as Map<String, dynamic>)).toList(),
      scadenza: json['scadenza'] != null
          ? DateTime.tryParse(json['scadenza'] as String)
          : null,
    );
  }
}
