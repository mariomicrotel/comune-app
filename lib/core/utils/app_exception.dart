sealed class AppException implements Exception {
  const AppException(this.message);
  final String message;

  @override
  String toString() => message;
}

class NetworkException extends AppException {
  const NetworkException([String message = 'Nessuna connessione internet.'])
      : super(message);
}

class ServerException extends AppException {
  final int? statusCode;
  const ServerException([
    String message = 'Errore del server. Riprova più tardi.',
    this.statusCode,
  ]) : super(message);
}

class UnauthorizedException extends AppException {
  const UnauthorizedException([String message = 'Accesso non autorizzato.'])
      : super(message);
}

class NotFoundException extends AppException {
  const NotFoundException([String message = 'Risorsa non trovata.'])
      : super(message);
}

class ParseException extends AppException {
  const ParseException([String message = 'Formato dati non valido.'])
      : super(message);
}

class UnknownException extends AppException {
  const UnknownException([String message = 'Errore sconosciuto.'])
      : super(message);
}
