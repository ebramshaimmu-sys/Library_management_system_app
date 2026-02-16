import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:frontend/services/api_service.dart';
import 'package:frontend/models/book.dart';

class BookListScreen extends StatefulWidget {
  const BookListScreen({super.key});

  @override
  State<BookListScreen> createState() => _BookListScreenState();
}

class _BookListScreenState extends State<BookListScreen> {
  List<Book> _books = [];
  List<Book> _filteredBooks = [];
  bool _isLoading = true;
  final _searchController = TextEditingController();
  Map<String, dynamic>? _user;

  @override
  void initState() {
    super.initState();
    _loadUser();
    _loadBooks();
    _searchController.addListener(_filterBooks);
  }
  
  void _loadUser() async {
     final user = await Provider.of<ApiService>(context, listen: false).getCurrentUser();
     if (mounted) setState(() => _user = user);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _loadBooks() async {
    try {
      final data = await Provider.of<ApiService>(context, listen: false).getBooks();
      if (mounted) {
        setState(() {
          _books = data.map((json) => Book.fromJson(json)).toList();
          _filteredBooks = _books;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading books: $e')),
        );
        setState(() => _isLoading = false);
      }
    }
  }
  
  void _filterBooks() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredBooks = _books.where((book) {
        return book.title.toLowerCase().contains(query) ||
               book.author.toLowerCase().contains(query) ||
               book.category.toLowerCase().contains(query);
      }).toList();
    });
  }

  void _deleteBook(int id) async {
    try {
      await Provider.of<ApiService>(context, listen: false).deleteBook(id);
      _loadBooks(); // refresh
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Book deleted successfully')),
        );
      }
    } catch (e) {
      if (mounted) {
         ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error deleting book: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isAdmin = _user != null && _user!['role'] == 'admin';

    return Scaffold(
      appBar: AppBar(title: const Text('Book Catalog')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              decoration: const InputDecoration(
                labelText: 'Search Books',
                prefixIcon: Icon(Icons.search),
              ),
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _filteredBooks.isEmpty
                  ? const Center(child: Text('No books found.'))
                  : ListView.builder(
                      itemCount: _filteredBooks.length,
                      itemBuilder: (context, index) {
                        final book = _filteredBooks[index];
                        return Card(
                          margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          child: ListTile(
                            title: Text(book.title, style: const TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Author: ${book.author}'),
                                Text('Category: ${book.category}'),
                                Text('Available: ${book.availableCopies} / ${book.totalCopies}'),
                              ],
                            ),
                            trailing: isAdmin 
                              ? IconButton(
                                  icon: const Icon(Icons.delete, color: Colors.red),
                                  onPressed: () => showDialog(
                                    context: context,
                                    builder: (ctx) => AlertDialog(
                                      title: const Text('Delete Book'),
                                      content: const Text('Are you sure you want to delete this book?'),
                                      actions: [
                                        TextButton(
                                          onPressed: () => Navigator.pop(ctx),
                                          child: const Text('Cancel'),
                                        ),
                                        TextButton(
                                          onPressed: () {
                                            Navigator.pop(ctx);
                                            _deleteBook(book.id);
                                          },
                                          child: const Text('Delete'),
                                        ),
                                      ],
                                    ),
                                  ),
                                )
                              : null,
                          ),
                        );
                      },
                    ),
          ),
        ],
      ),
      floatingActionButton: isAdmin ? FloatingActionButton(
        onPressed: () => Navigator.pushNamed(context, '/add-book').then((_) => _loadBooks()),
        child: const Icon(Icons.add),
      ) : null,
    );
  }
}
