class Ufficio {
  final int id;
  final String nome;
  final String? responsabile;
  final String? tel;
  final String? email;
  final String? pec;
  final String? indirizzo;
  final Map<String, String> orari;
  final String categoria;

  const Ufficio({
    required this.id,
    required this.nome,
    this.responsabile,
    this.tel,
    this.email,
    this.pec,
    this.indirizzo,
    required this.orari,
    required this.categoria,
  });

  factory Ufficio.fromJson(Map<String, dynamic> json) {
    final orariRaw = json['orari'];
    Map<String, String> orari = {};
    if (orariRaw is Map) {
      orari = orariRaw.map((k, v) => MapEntry(k.toString(), v.toString()));
    }
    return Ufficio(
      id: json['id'] as int,
      nome: json['nome'] as String? ?? '',
      responsabile: json['responsabile'] as String?,
      tel: json['tel'] as String?,
      email: json['email'] as String?,
      pec: json['pec'] as String?,
      indirizzo: json['indirizzo'] as String?,
      orari: orari,
      categoria: json['categoria'] as String? ?? '',
    );
  }
}
