<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Payum\Core\Request\GetHumanStatus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Range;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Core\Registry\RegistryInterface;



class PaymentController extends Controller
{

    /**
     * @Route("/payment/{amount}", name="payment", requirements={"amount" = "\d+"}, defaults={"amount" = 1})
     */

    public function preparePaypalExpressCheckoutPayment($amount)
    {
        $gatewayName = 'paypal';


        $storage = $this->get('payum')->getStorage('App\Entity\PaymentDetails');

        /** @var \App\Entity\PaymentDetails $details */
        $details = $storage->create();
        $details['PAYMENTREQUEST_0_CURRENCYCODE'] = 'EUR';
        $details['PAYMENTREQUEST_0_AMT'] = $amount;
        $details['PAYMENTREQUEST_0_LOCALECODE']='nl_NL';
        $storage->update($details);

        $captureToken = $this->get('payum')->getTokenFactory()->createCaptureToken(
            $gatewayName,
            $details,
            'done' // the route to redirect after capture;
        );

        return $this->redirect($captureToken->getTargetUrl());
    }


    /**
     * @Route("/payment/done", name="done")
     */

    public function done(Request $request)
    {
        $token = $this->get('payum')->getHttpRequestVerifier()->verify($request);

        $gateway = $this->get('payum')->getGateway($token->getGatewayName());

        // You can invalidate the token, so that the URL cannot be requested any more:
         //$this->get('payum')->getHttpRequestVerifier()->invalidate($token);

        // Once you have the token, you can get the payment entity from the storage directly.
        // $identity = $token->getDetails();
        // $payment = $this->get('payum')->getStorage($identity->getClass())->find($identity);

        // Or Payum can fetch the entity for you while executing a request (preferred).
        $gateway->execute($status = new GetHumanStatus($token));
        $payment = $status->getFirstModel();

        // Now you have order and payment status

        return new JsonResponse(array(
            'status' => $status->getValue(),
           'details' => iterator_to_array($payment)


        ));
    }


}
