class Book {
  final int id;
  final String title;
  final String author;
  final String category;
  final int totalCopies;
  final int availableCopies;

  Book({
    required this.id,
    required this.title,
    required this.author,
    required this.category,
    required this.totalCopies,
    required this.availableCopies,
  });

  factory Book.fromJson(Map<String, dynamic> json) {
    return Book(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      title: json['title'],
      author: json['author'],
      category: json['category'] ?? '',
      totalCopies: json['total_copies'] is int ? json['total_copies'] : int.parse(json['total_copies'].toString()),
      availableCopies: json['available_copies'] is int ? json['available_copies'] : int.parse(json['available_copies'].toString()),
    );
  }
}
