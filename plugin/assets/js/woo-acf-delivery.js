document.addEventListener("DOMContentLoaded", function () {
    console.log("Custom Cart Text script loaded!");

    // Check for the presence of the WooCommerce cart block using a Promise
    async function waitForCartBlock() {
        const cartBlock = await new Promise((resolve) => {
            const interval = setInterval(() => {
                const cartElement = document.querySelector('.wc-block-cart');
                if (cartElement) {
                    clearInterval(interval);
                    resolve(cartElement);
                }
            }, 100);
        });

        //console.log("WooCommerce Blocks cart is loaded!");
        monitorCartItems(cartBlock);
    }

    // Monitor for cart item changes using MutationObserver
    function monitorCartItems(cartBlock) {
        const observer = new MutationObserver(() => {
            const cartItems = cartBlock.querySelectorAll('.wc-block-components-product-name');

            if (cartItems.length > 0) {
                //console.log("Cart items detected!");

                cartItems.forEach(item => {
                    const productName = item.textContent.trim();

                    // Find the cart item matching this product name
                    const cartItem = Object.values(cartData.items).find(ci =>
                        ci.data && ci.data.product_name && ci.data.product_name.trim() === productName
                    );

                    //console.log(`Checking product: ${productName}`, cartItem);

                    if (cartItem && cartItem.delayed_delivery === "1") {
                        if (!item.hasAttribute('data-special-offer-applied')) {
                            addSpecialOfferToCart(item);
                        }
                    }
                });

                observer.disconnect();
            }
        });

        observer.observe(cartBlock, { childList: true, subtree: true });
    }

    // Add special offer text to a single cart item
    function addSpecialOfferToCart(item) {
        const offerContainer = document.createElement('div');
        offerContainer.classList.add('special-offer-container');

        const specialOfferText = document.createElement('p');
        specialOfferText.classList.add('special-offer-text');
        specialOfferText.style.color = 'green';
        specialOfferText.textContent = 'Special Offer: Free Shipping!';

        offerContainer.appendChild(specialOfferText);
        item.parentNode.insertBefore(offerContainer, item.nextSibling);

        //console.log(`Special offer added to ${item.textContent.trim()}!`);
        item.setAttribute('data-special-offer-applied', 'true');
    }

    waitForCartBlock();
});
