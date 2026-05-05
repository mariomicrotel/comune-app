import 'package:intl/intl.dart';

class DateFormatter {
  DateFormatter._();

  static final _dayMonth = DateFormat('d MMMM', 'it_IT');
  static final _dayMonthYear = DateFormat('d MMMM yyyy', 'it_IT');
  static final _dayMonthShort = DateFormat('d MMM', 'it_IT');
  static final _time = DateFormat('HH:mm', 'it_IT');
  static final _fullDateTime = DateFormat("d MMMM yyyy 'alle' HH:mm", 'it_IT');
  static final _dayName = DateFormat('EEEE', 'it_IT');
  static final _dayNameShort = DateFormat('EEE', 'it_IT');
  static final _dayNumber = DateFormat('d', 'it_IT');
  static final _monthYear = DateFormat('MMMM yyyy', 'it_IT');

  static String dayMonth(DateTime d) => _dayMonth.format(d);
  static String dayMonthYear(DateTime d) => _dayMonthYear.format(d);
  static String dayMonthShort(DateTime d) => _dayMonthShort.format(d);
  static String time(DateTime d) => _time.format(d);
  static String fullDateTime(DateTime d) => _fullDateTime.format(d);
  static String dayName(DateTime d) => _dayName.format(d);
  static String dayNameShort(DateTime d) => _dayNameShort.format(d);
  static String dayNumber(DateTime d) => _dayNumber.format(d);
  static String monthYear(DateTime d) => _monthYear.format(d);

  static String relative(DateTime d) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final target = DateTime(d.year, d.month, d.day);
    final diff = target.difference(today).inDays;

    if (diff == 0) return 'Oggi';
    if (diff == 1) return 'Domani';
    if (diff == -1) return 'Ieri';
    if (diff > 1 && diff <= 7) return 'Tra $diff giorni';
    return dayMonthYear(d);
  }

  static String relativeCompact(DateTime d) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final target = DateTime(d.year, d.month, d.day);
    final diff = target.difference(today).inDays;

    if (diff == 0) return 'Oggi';
    if (diff == 1) return 'Domani';
    return dayMonthShort(d);
  }

  static String eventRange(DateTime start, DateTime end) {
    if (start.year == end.year && start.month == end.month && start.day == end.day) {
      return '${dayMonth(start)}, ${time(start)}–${time(end)}';
    }
    return '${dayMonthShort(start)} – ${dayMonth(end)}';
  }
}
