import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:frontend/services/api_service.dart';
import 'package:frontend/models/book.dart';

class IssueReturnScreen extends StatefulWidget {
  const IssueReturnScreen({super.key});

  @override
  State<IssueReturnScreen> createState() => _IssueReturnScreenState();
}

class _IssueReturnScreenState extends State<IssueReturnScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final _issueFormKey = GlobalKey<FormState>();
  final _returnFormKey = GlobalKey<FormState>(); // Kept if we want manual entry, but we'll use list
  
  // Issue Data
  List<Book> _books = [];
  List<dynamic> _users = [];
  Book? _selectedBook;
  int? _selectedUserId;
  
  // Return Data
  final _transactionIdController = TextEditingController();

  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }
  
  void _loadData() async {
    try {
      final api = Provider.of<ApiService>(context, listen: false);
      final books = await api.getBooks();
      final users = await api.getUsers();
      
      if (mounted) {
        setState(() {
          _books = books.map((json) => Book.fromJson(json)).where((b) => b.availableCopies > 0).toList();
          _users = users;
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading data: $e')),
        );
      }
    }
  }

  void _issueBook() async {
    if (_issueFormKey.currentState!.validate() && _selectedBook != null && _selectedUserId != null) {
      setState(() => _isLoading = true);
      try {
        await Provider.of<ApiService>(context, listen: false).issueBook(_selectedUserId!, _selectedBook!.id);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Book issued successfully')),
          );
          _loadData(); // refresh availability
          // Reset selection
          setState(() {
             _selectedBook = null;
             _selectedUserId = null;
          });
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString()), backgroundColor: Colors.red),
          );
        }
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    } else {
       ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please select both user and book')),
        );
    }
  }

  void _returnBook() async {
    if (_transactionIdController.text.isNotEmpty) {
      setState(() => _isLoading = true);
      try {
        await Provider.of<ApiService>(context, listen: false).returnBook(int.parse(_transactionIdController.text));
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Book returned successfully')),
          );
          _transactionIdController.clear();
          _loadData(); // refresh
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString()), backgroundColor: Colors.red),
          );
        }
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Issue / Return'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Issue Book'),
            Tab(text: 'Return Book'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildIssueTab(),
          _buildReturnTab(),
        ],
      ),
    );
  }

  Widget _buildIssueTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24.0),
      child: Form(
        key: _issueFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Issue a Book',
               style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 24),
            DropdownButtonFormField<int>(
              decoration: const InputDecoration(labelText: 'Select User'),
              initialValue: _selectedUserId,
              items: _users.map<DropdownMenuItem<int>>((user) {
                return DropdownMenuItem<int>(
                  value: int.tryParse(user['id'].toString()),
                  child: Text('${user['name']} (${user['email']})'),
                );
              }).toList(),
              onChanged: (value) => setState(() => _selectedUserId = value),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<Book>(
              decoration: const InputDecoration(labelText: 'Select Book'),
              initialValue: _selectedBook,
              items: _books.map<DropdownMenuItem<Book>>((book) {
                return DropdownMenuItem<Book>(
                  value: book,
                  child: Text('${book.title} (Avail: ${book.availableCopies})'),
                );
              }).toList(),
              onChanged: (value) => setState(() => _selectedBook = value),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _isLoading ? null : _issueBook,
              child: _isLoading 
                ? const CircularProgressIndicator() 
                : const Text('Issue Book'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReturnTab() {
    return Padding(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Return a Book',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          const Text('Enter the Transaction ID provided when getting the book history.'),
          const SizedBox(height: 24),
          TextField(
            controller: _transactionIdController,
            decoration: const InputDecoration(
              labelText: 'Transaction ID',
              prefixIcon: Icon(Icons.receipt),
            ),
            keyboardType: TextInputType.number,
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: _isLoading ? null : _returnBook,
            style: ElevatedButton.styleFrom(backgroundColor: Colors.orange.shade100),
            child: _isLoading 
                ? const CircularProgressIndicator() 
                : const Text('Return Book', style: TextStyle(color: Colors.brown)),
          ),
        ],
      ),
    );
  }
}
