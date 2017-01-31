# LegalServerPhoneAPI
A PHP way to access the legal server phone api.
<pre>
$phonegetter = new LSPhoneAPI('LSUsername','LSPassword','OrgSubdomain');
try{ 
  print_r($phonegetter->searchPhoneNumber('3018675309'));
  print_r($phonegetter->getMatter(01234567));
}catch(Exception $e){
  echo "Error: " . $e->getMessage();
}
</pre>
