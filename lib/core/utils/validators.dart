class Validators {
  Validators._();

  static String? required(String? value, [String fieldName = 'campo']) {
    if (value == null || value.trim().isEmpty) {
      return 'Il $fieldName è obbligatorio.';
    }
    return null;
  }

  static String? minLength(String? value, int min, [String fieldName = 'campo']) {
    if (value == null || value.trim().length < min) {
      return 'Il $fieldName deve contenere almeno $min caratteri.';
    }
    return null;
  }

  static String? maxLength(String? value, int max, [String fieldName = 'campo']) {
    if (value != null && value.length > max) {
      return 'Il $fieldName non può superare $max caratteri.';
    }
    return null;
  }

  static String? description(String? value) {
    return minLength(value, 20, 'descrizione');
  }
}
