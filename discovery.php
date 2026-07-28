$tables = ['blog_categories', 'courses_categories'];
foreach($tables as $table) {
    echo "Table: " . $table . "\n";
    $data = DB::table($table)->select('id', 'name')->get();
    echo json_encode($data) . "\n\n";
}
