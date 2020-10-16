jQuery( document ).ready( ( $ ) => {

	"use strict"

	/**
	 * Google Pay handler.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @type {SV_WC_Google_Pay_Handler_v5_8_1} object
	 */
	window.SV_WC_Google_Pay_Handler_v5_8_1 = class SV_WC_Google_Pay_Handler_v5_8_1 {

		/**
		 * Handler constructor.
		 *
		 * @since 5.9.0-dev.1
		 *
		 * @param {Object} params The plugin ID
		 * @param {string} params.plugin_id The plugin ID
		 * @param {string} params.merchant_id The merchant ID
		 * @param {string} params.merchant_name The site name
		 * @param {string} params.gateway_id The gateway ID
		 * @param {string} params.gateway_id_dasherized The gateway ID dasherized
		 * @param {string} params.ajax_url The AJAX URL
		 * @param {string} params.recalculate_totals_nonce Nonce for the recalculate_totals AJAX action
		 * @param {string} params.process_nonce Nonce for the process AJAX action
		 * @param {string} params.button_style The button style
		 * @param {string[]} params.card_types The supported card types
		 * @param {string} params.generic_error The generic error message
		 * @param {string} params.product_id The product ID if we are on a Product page
		 */
		constructor(params) {

			let {
				plugin_id,
				merchant_id,
				merchant_name,
				gateway_id,
				gateway_id_dasherized,
				ajax_url,
				recalculate_totals_nonce,
				process_nonce,
				button_style,
				card_types,
				generic_error
			} = params;

			this.gatewayID = gateway_id;
			this.merchantID = merchant_id;
			this.merchantName = merchant_name;
			this.ajaxURL = ajax_url;
			this.recalculateTotalsNonce = recalculate_totals_nonce;
			this.processNonce = process_nonce;
			this.genericError = generic_error;

			if (params.product_id) {
				this.productID = params.product_id;
			}

			/**
			 * Card networks supported by your site and your gateway
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
			 */
			const allowedCardNetworks = card_types;

			/**
			 * Define the version of the Google Pay API referenced when creating your configuration
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|apiVersion in PaymentDataRequest}
			 */
			this.baseRequest = {
				apiVersion: 2,
				apiVersionMinor: 0
			};

			/**
			 * Card authentication methods supported by your site and your gateway
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
			 *
			 * @todo confirm your processor supports Android device tokens for your supported card networks
			 */
			const allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];

			/**
			 * Identify your gateway and your site's gateway merchant identifier
			 *
			 * The Google Pay API response will return an encrypted payment method capable
			 * of being charged by a supported gateway after payer authorization
			 *
			 * @todo check with your gateway on the parameters to pass
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#gateway|PaymentMethodTokenizationSpecification}
			 */
			const tokenizationSpecification = {
				type: 'PAYMENT_GATEWAY',
				parameters: {
					'gateway': plugin_id,
					'gatewayMerchantId': this.merchantID
				}
			};

			/**
			 * Describe your site's support for the CARD payment method and its required fields
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
			 */
			this.baseCardPaymentMethod = {
				type: 'CARD',
				parameters: {
					allowedAuthMethods: allowedCardAuthMethods,
					allowedCardNetworks: allowedCardNetworks
				}
			};

			/**
			 * Describe your site's support for the CARD payment method including optional fields
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
			 */
			this.cardPaymentMethod = Object.assign(
				{},
				this.baseCardPaymentMethod,
				{
					tokenizationSpecification: tokenizationSpecification
				}
			);

			/**
			 * An initialized google.payments.api.PaymentsClient object or null if not yet set
			 *
			 * @see {@link getGooglePaymentsClient}
			 */
			this.paymentsClient = null;
		}

		/**
		 * Configure your site's support for payment methods supported by the Google Pay
		 * API.
		 *
		 * Each member of allowedPaymentMethods should contain only the required fields,
		 * allowing reuse of this base request when determining a viewer's ability
		 * to pay and later requesting a supported payment method
		 *
		 * @returns {object} Google Pay API version, payment methods supported by the site
		 */
		getGoogleIsReadyToPayRequest() {

			return Object.assign(
				{},
				this.baseRequest,
				{
					allowedPaymentMethods: [this.baseCardPaymentMethod]
				}
			);
		}

		/**
		 * Configure support for the Google Pay API
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|PaymentDataRequest}
		 *
		 * @param {function} resolve callback
		 * @returns {object} PaymentDataRequest fields
		 */
		getGooglePaymentDataRequest( resolve ) {

			return this.getGoogleTransactionInfo( ( transactionInfo ) => {

				console.log( 'transactionInfo' );
				console.log( transactionInfo );

				const paymentDataRequest = Object.assign({}, this.baseRequest);
				paymentDataRequest.allowedPaymentMethods = [this.cardPaymentMethod];

				paymentDataRequest.transactionInfo = transactionInfo;

				console.log(paymentDataRequest);

				paymentDataRequest.merchantInfo = {
					merchantId: this.merchantID,
					merchantName: this.merchantName
				};

				paymentDataRequest.callbackIntents = ["SHIPPING_ADDRESS", "SHIPPING_OPTION", "PAYMENT_AUTHORIZATION"];
				paymentDataRequest.shippingAddressRequired = true;
				paymentDataRequest.shippingAddressParameters = this.getGoogleShippingAddressParameters();
				paymentDataRequest.shippingOptionRequired = true;

				resolve( paymentDataRequest );
			} );
		}

		/**
		 * Return an active PaymentsClient or initialize
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/client#PaymentsClient|PaymentsClient constructor}
		 * @returns {google.payments.api.PaymentsClient} Google Pay API client
		 */
		getGooglePaymentsClient() {
			if ( this.paymentsClient === null ) {
				this.paymentsClient = new google.payments.api.PaymentsClient({
					merchantInfo: {
						merchantName: this.merchantName,
						merchantId: this.merchantID
					},
					paymentDataCallbacks: {
						onPaymentAuthorized: (paymentData) => this.onPaymentAuthorized(paymentData),
						onPaymentDataChanged: (paymentData) => this.onPaymentDataChanged(paymentData)
					}
				});
			}
			return this.paymentsClient;
		}

		onPaymentAuthorized(paymentData) {

			console.log('onPaymentAuthorized');
			console.log(paymentData);

			return new Promise((resolve, reject) => {

				// handle the response
				try {
					this.processPayment(paymentData, resolve);
						// .then(() => {
						// 	resolve({transactionState: 'SUCCESS'});
						// })
						// .catch(() => {
						// 	console.log('catch');
						// 	resolve({
						// 		transactionState: 'ERROR',
						// 		error: {
						// 			intent: 'SHIPPING_ADDRESS',
						// 			message: 'Invalid data',
						// 			reason: 'PAYMENT_DATA_INVALID'
						// 		}
						// 	});
						// });
				}	catch(err) {
					console.log('catch');
					// show error in developer console for debugging
					console.error(err);
					reject({
						transactionState: 'ERROR',
						error: {
							intent: 'PAYMENT_AUTHORIZATION',
							message: 'Insufficient funds',
							reason: 'PAYMENT_DATA_INVALID'
						}
					});
				}
			});
		}

		/**
		 * Handles dynamic buy flow shipping address and shipping options callback intents.
		 *
		 * @param {object} intermediatePaymentData response from Google Pay API a shipping address or shipping option is selected in the payment sheet.
		 * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#IntermediatePaymentData|IntermediatePaymentData object reference}
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentDataRequestUpdate|PaymentDataRequestUpdate}
		 * @returns Promise<{object}> Promise of PaymentDataRequestUpdate object to update the payment sheet.
		 */
		onPaymentDataChanged(intermediatePaymentData) {

			console.log('onPaymentDataChanged');
			console.log(intermediatePaymentData);

			return new Promise((resolve, reject) => {

				console.log(resolve);
				console.log(reject);

				try {
					let shippingAddress = intermediatePaymentData.shippingAddress;
					let shippingOptionData = intermediatePaymentData.shippingOptionData;
					let chosenShippingMethod = '';

					if (intermediatePaymentData.callbackTrigger == "SHIPPING_OPTION") {
						chosenShippingMethod = shippingOptionData.id;
					}

					this.getUpdatedTotals( shippingAddress, chosenShippingMethod, ( paymentDataRequestUpdate ) => {

						if (paymentDataRequestUpdate.newShippingOptionParameters.shippingOptions.length == 0) {
							paymentDataRequestUpdate = {
								error: this.getGoogleUnserviceableAddressError()
							};
						}

						console.log('paymentDataRequestUpdate');
						console.log(paymentDataRequestUpdate);

						resolve(paymentDataRequestUpdate);
					});

				}	catch(err) {
					console.log('catch');
					// show error in developer console for debugging
					console.error(err);
					reject({
						transactionState: 'ERROR',
						error: {
							intent: 'PAYMENT_AUTHORIZATION',
							message: 'Insufficient funds',
							reason: 'PAYMENT_DATA_INVALID'
						}
					});
				}
			});
		}

		/**
		 * Provide Google Pay API with a payment amount, currency, and amount status
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#TransactionInfo|TransactionInfo}
		 *
		 * @param {function} resolve callback
		 * @returns {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
		 */
		getGoogleTransactionInfo( resolve ) {

			// get transaction info from cart
			const data = {
				action: `wc_${this.gatewayID}_google_pay_get_transaction_info`,
			}

			if (this.productID) {
				data.productID = this.productID;
			}

			$.post(this.ajaxURL, data, ( response ) => {

				console.log(response);

				if (response.success) {
					resolve( $.parseJSON( response.data ) )
				} else {
					this.fail_payment( 'Could not build transaction info. ' + response.data.message );
				}
			} );
		}

		/**
		 * Get updated totals and shipping options via AJAX for use in the PaymentDataRequest
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentDataRequestUpdate|PaymentDataRequestUpdate}
		 *
		 * @param {object} shippingAddress shipping address
		 * @param {object} shippingMethod chosen shipping method
		 * @param {function} resolve callback
		 */
		getUpdatedTotals( shippingAddress, shippingMethod, resolve ) {

			const data = {
				action: `wc_${this.gatewayID}_google_pay_recalculate_totals`,
				'nonce': this.recalculateTotalsNonce,
				shippingAddress,
				shippingMethod
			}

			if (this.productID) {
				data.productID = this.productID;
			}

			$.post(this.ajaxURL, data, ( response ) => {

				console.log(response);

				if (response.success) {
					resolve( $.parseJSON( response.data ) )
				} else {
					this.fail_payment( 'Could not recalculate totals. ' + response.data.message );
				}
			} );
		}

		/**
		 * Provide Google Pay API with shipping address parameters when using dynamic buy flow.
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#ShippingAddressParameters|ShippingAddressParameters}
		 * @returns {object} shipping address details, suitable for use as shippingAddressParameters property of PaymentDataRequest
		 */
		getGoogleShippingAddressParameters() {

			return  {
				// @todo: get from WC
				allowedCountryCodes: ['US'],
				phoneNumberRequired: true
			};
		}

		/**
		 * Provide Google Pay API with a payment data error.
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentDataError|PaymentDataError}
		 * @returns {object} payment data error, suitable for use as error property of PaymentDataRequestUpdate
		 */
		getGoogleUnserviceableAddressError() {
			return {
				reason: "SHIPPING_ADDRESS_UNSERVICEABLE",
				message: "Cannot ship to the selected address",
				intent: "SHIPPING_ADDRESS"
			};
		}

		/**
		 * Add a Google Pay purchase button alongside an existing checkout button
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#ButtonOptions|Button options}
		 * @see {@link https://developers.google.com/pay/api/web/guides/brand-guidelines|Google Pay brand guidelines}
		 */
		addGooglePayButton() {

			const paymentsClient = this.getGooglePaymentsClient();
			const button = paymentsClient.createButton({
				onClick: (event) => this.onGooglePaymentButtonClicked( event )
			});
			document.getElementById('sv-wc-google-pay-button-container').appendChild(button);
		}

		/**
		 * Prefetch payment data to improve performance
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/client#prefetchPaymentData|prefetchPaymentData()}
		 */
		prefetchGooglePaymentData() {

			this.getGooglePaymentDataRequest( ( paymentDataRequest ) => {

				// transactionInfo must be set but does not affect cache
				paymentDataRequest.transactionInfo = {
					totalPriceStatus: 'NOT_CURRENTLY_KNOWN',
					currencyCode: 'USD'
				};
				const paymentsClient = this.getGooglePaymentsClient();
				paymentsClient.prefetchPaymentData(paymentDataRequest);
			} );
		}

		/**
		 * Process payment data returned by the Google Pay API
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentData|PaymentData object reference}
		 *
		 * @param {object} paymentData response from Google Pay API after user approves payment
		 * @param {function} resolve callback
		 */
		processPayment(paymentData, resolve) {

			// show returned data in developer console for debugging
			console.log(paymentData);

			// pass payment token to your gateway to process payment
			const data = {
				action: `wc_${this.gatewayID}_google_pay_process_payment`,
				nonce: this.processNonce,
				paymentData: JSON.stringify(paymentData)
			}

			return $.post(this.ajaxURL, data, (response) => {
				if (response.success) {
					resolve({transactionState: 'SUCCESS'});
					window.location = response.data.redirect;
				} else {
					resolve({
						transactionState: 'ERROR',
						error: {
							intent: 'SHIPPING_ADDRESS',
							message: 'Invalid data',
							reason: 'PAYMENT_DATA_INVALID'
						}
					});
					this.fail_payment( 'Payment could no be processed. ' + response.data.message );
				}
			});
		}

		/**
		 * Show Google Pay payment sheet when Google Pay payment button is clicked
		 */
		onGooglePaymentButtonClicked(event) {

			event.preventDefault();

			this.block_ui();

			this.getGooglePaymentDataRequest( ( paymentDataRequest ) => {

				console.log(paymentDataRequest);
				console.log(paymentDataRequest.transactionInfo);
				console.log(paymentDataRequest.transactionInfo.displayItems);
				const paymentsClient = this.getGooglePaymentsClient();
				try {
					paymentsClient.loadPaymentData(paymentDataRequest);
				} catch (err) {
					// show error in developer console for debugging
					console.error(err);
				}
			});
		}

		/**
		 * Initialize Google PaymentsClient after Google-hosted JavaScript has loaded
		 *
		 * Display a Google Pay payment button after confirmation of the viewer's
		 * ability to pay.
		 */
		init() {

			// initialize for the various pages
			if ($('form.cart').length) {
				this.init_product_page();
			} else if ($('form.woocommerce-cart-form').length) {
				this.init_cart_page();
			} else if ($('form.woocommerce-checkout').length) {
				this.init_checkout_page()
			} else {
				return;
			}

			const paymentsClient = this.getGooglePaymentsClient();
			paymentsClient.isReadyToPay(this.getGoogleIsReadyToPayRequest())
				.then((response) => {
					if (response.result) {
						this.addGooglePayButton();
						// @todo prefetch payment data to improve performance after confirming site functionality
						// this.prefetchGooglePaymentData();
					}
				})
				.catch((err) => {
					// show error in developer console for debugging
					console.error(err);
				});
		}

		/**
		 * Initializes the product page.
		 */
		init_product_page() {
			this.ui_element = $('form.cart');
		}

		/**
		 * Initializes the cart page.
		 */
		init_cart_page() {
			this.ui_element = $('form.woocommerce-cart-form').parents('div.woocommerce');
		}

		/**
		 * Initializes the checkout page.
		 */
		init_checkout_page() {
			this.ui_element = $('form.woocommerce-checkout');
		}

		/**
		 * Fails the purchase based on the gateway result.
		 */
		fail_payment ( error ) {

			console.error( '[Google Pay] ' + error );

			this.unblock_ui();

			this.render_errors( [ this.genericError ] );
		}

		/**
		 * Renders any new errors and bring them into the viewport.
 		 */
		render_errors( errors ) {

			// hide and remove any previous errors
			$('.woocommerce-error, .woocommerce-message').remove();

			// add errors
			this.ui_element.prepend('<ul class="woocommerce-error"><li>' + errors.join('</li><li>') + '</li></ul>');

			// unblock UI
			this.ui_element.removeClass('processing').unblock();

			// scroll to top
			$('html, body').animate({scrollTop: this.ui_element.offset().top - 100}, 1000);
		}

		/**
		 * Blocks the payment form UI.
		 */
		block_ui() {
			this.ui_element.block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
		}

		/**
		 * Unblocks the payment form UI.
		 */
		unblock_ui() {
			this.ui_element.unblock();
		}
	}

	$( document.body ).trigger( 'sv_wc_google_pay_handler_v5_8_1_loaded' );

} );
