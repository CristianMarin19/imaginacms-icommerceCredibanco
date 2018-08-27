# asgardcms-icommercecredibanco

### Add Except

1. Go to app/http/middleware/VerifyCsrfToken
2. add this:  

	protected $except = [
        '/icommercecredibanco/response'
    ];

### Data Configuration

	1. Create the keys on VPayment (Credibanco's Aplication)
	2. Save the keys in a directory

		- If they are the TEST keys, within the directory that you created, create a directory called "test" and place the keys there.

		Example: mydirectory/test/key1.txt

		- If they are the PRODUCTION keys, just place them inside the directory that you chose.
		
		Example: mydirectory/key1.txt


	3. In the ASGARD BACKEND save the Files names with extensions. 
		Example: "key1.txt"


	