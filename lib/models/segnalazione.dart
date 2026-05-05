enum StatoSegnalazione { ricevuta, presaInCarico, inLavorazione, chiusa }

extension StatoSegnalazioneX on StatoSegnalazione {
  String get label {
    switch (this) {
      case StatoSegnalazione.ricevuta:
        return 'Aperta';
      case StatoSegnalazione.presaInCarico:
        return 'Presa in carico';
      case StatoSegnalazione.inLavorazione:
        return 'In lavorazione';
      case StatoSegnalazione.chiusa:
        return 'Risolta';
    }
  }

  static StatoSegnalazione fromString(String? s) {
    switch (s) {
      case 'in_progress':
      case 'in_lavorazione':
        return StatoSegnalazione.inLavorazione;
      case 'resolved':
      case 'chiusa':
        return StatoSegnalazione.chiusa;
      case 'pending':
      case 'ricevuta':
        return StatoSegnalazione.ricevuta;
      default:
        return StatoSegnalazione.ricevuta;
    }
  }
}

class Segnalazione {
  final String? id;
  final String? publicCode;
  final String categoria;
  final String descrizione;
  final List<String> fotoUrls;
  final double? lat;
  final double? lng;
  final String? indirizzo;
  final StatoSegnalazione stato;
  final DateTime data;
  final String deviceId;

  const Segnalazione({
    this.id,
    this.publicCode,
    required this.categoria,
    required this.descrizione,
    required this.fotoUrls,
    this.lat,
    this.lng,
    this.indirizzo,
    required this.stato,
    required this.data,
    required this.deviceId,
  });

  factory Segnalazione.fromJson(Map<String, dynamic> json) {
    return Segnalazione(
      id: json['id']?.toString(),
      publicCode: json['public_code'] as String?,
      categoria: json['categoria'] as String? ?? '',
      descrizione: json['descrizione'] as String? ?? '',
      fotoUrls: List<String>.from(json['foto_urls'] as List? ?? []),
      lat: (json['lat'] as num?)?.toDouble(),
      lng: (json['lng'] as num?)?.toDouble(),
      indirizzo: json['indirizzo'] as String?,
      stato: StatoSegnalazioneX.fromString(json['stato'] as String?),
      data: DateTime.tryParse(json['data'] as String? ?? '') ?? DateTime.now(),
      deviceId: json['device_id'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
        'categoria': categoria,
        'descrizione': descrizione,
        if (lat != null) 'lat': lat,
        if (lng != null) 'lng': lng,
        if (indirizzo != null) 'indirizzo': indirizzo,
        'device_id': deviceId,
      };
}
