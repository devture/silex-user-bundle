<?php
namespace Devture\Bundle\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class BrowserIdController extends BaseController {

	public function loginAction(Request $request) {
		//Proceed even if there is someone logged in at the moment ($this->get('user') !== null).
		//That's because navigator.id.watch()/onlogin (which hits this) is called in all browser tabs,
		//no matter which tab initiated the login process.
		//--
		//Just replying with `ok => true` may not be safe in all cases though, as it implies the assertion is valid.
		//Having a logged in user does NOT mean it's the same as the one in the assertion.
		//If by some chance it's not, we need to reply with a failure!
		//--
		//You'll notice below that we only perform the actual login procedure only if the user changes.

		if (!$request->request->has('assertion')) {
			return $this->abort(400);
		}

		$response = $this->json(array('ok' => false));

		$user = $this->getAuthHelper()->authenticateWithBrowserIdAssertion($request->request->get('assertion'));

		if ($user !== null) {
			//This login is a success, no matter what happens below.
			$response = $this->json(array('ok' => true));

			if ($this->get('user') !== $user) {
				//Only perform the actual login procedure (setting cookies, whatever),
				//if no one was previously logged in (fresh login) or if someone different was (user change).
				$this->getLoginManager()->login($user, $response);
			}
		}

		return $response;
	}

}