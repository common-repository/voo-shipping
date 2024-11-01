jQuery(document).ready(function($){
    // alert('Hello World!');
    $(".cancel-button").on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: "Please Wait While Send Your Cancel Request",
            icon: 'info',
            showConfirmButton: false,
        })
        let target = $(this).attr('data-key');
        let token = $(this).attr('bearerToken');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Authorization': `${token}`,
                },
            url:`https://portal.eg.sideup.co/api/cancelWooCommerceOrder`,
            data: {
                woocommerce_id: target,
            },
            method: "POST",
            success:function(response){
                return true;
            },
            error:function(error){
                return false;
            },
        }).then(response => {
            location.reload();
        }).catch(e => {
            Swal.fire({
                title: 'It seems you missed something, please contact with your account manager to help you and solve this issue.',
                confirmButtonText: `OK`,
            })
        })
    });


jQuery(document).ready(function($) {
    let token = sideupSettings.bearerToken;

    $(document).on('click', '.shipping-button', function(e) {
    e.preventDefault();

    let token = sideupSettings.bearerToken; 
    let orderRow = $(this).closest('tr');
    let orderId = parseInt(orderRow.attr('id').replace('post-', ''));
    
    let orderData = sideupOrdersData.find(order => order.orderId == orderId);

    if (!orderData) {
        Swal.fire('Error', 'Order data not found.', 'error');
        return;
    }

    // عرض رسالة انتظار أثناء معالجة الطلب
    Swal.fire({
        title: 'Please wait',
        text: 'Fetching local prices...',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    let dropAreaName = orderData.state;

    if (!dropAreaName) {
        Swal.fire('Error', `Please add address for order ${orderId}.`, 'error');
        return; // Stop further execution if address is missing
    }
    

    let apiUrl = `https://portal.eg.sideup.co/api/localPrices?orders[0][order_id]=${orderData.orderId}&orders[0][drop_area_name]=${encodeURIComponent(dropAreaName)}`;

    $.ajax({
        url: apiUrl,
        method: 'GET',
        headers: {
            'Authorization': `${token}`
        },
        success: function(response) {
            Swal.close();

            if (!response || !response.data || !response.data[orderId]) {
                Swal.fire('Error', 'No carriers found for this order. Please contact support.', 'error');
                return;
            }

            let carriers = response.data[orderId].prices;
            let carriersOptions = '';

            Object.keys(carriers).forEach(carrier => {
                let price = carriers[carrier];
                carriersOptions += `
                    <div style="display:inline-block; margin-right: 10px;">
                        <input type="radio" name="carrier_${orderId}" value="${carrier}" id="carrier_${orderId}_${carrier}">
                        <label for="carrier_${orderId}_${carrier}">${carrier} - ${price} EGP</label>
                    </div>
                `;
            });

            let popupContent = `
            <style>
                .custom-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .custom-table th, .custom-table td {
                    border: 1px solid #ddd;
                    padding: 5px;
                    text-align: center;
                    font-size: 12px;
                }
                .custom-table th {
                    background-color: #f2f2f2;
                    color: #333;
                    font-weight: bold;
                }
                .custom-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .custom-table tr:hover {
                    background-color: #f1f1f1;
                }
            </style>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Payment Method</th>
                        <th>Total Cost</th>
                        <th>Carriers</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${orderId}</td>
                        <td>${orderData.customer_name}</td>
                        <td><input type="text" class="phone-input" value="${orderData.phone}" /></td>
                        <td>
                            <select class="dropdown-select" name="paymentMethod_${orderId}">
                                <option value="4">Cash On Delivery</option>
                                <option value="1">Credit Card (Visa or Mastercard)</option>
                                <option value="2">Fawry</option>
                                <option value="3">Zero Cash Collection</option>
                            </select>
                        </td>
                        <td>${orderData.total_cost}</td>
                        <td>${carriersOptions}</td>
                    </tr>
                </tbody>
            </table>
            <div style="text-align: center; margin-top: 20px;">
                <button id="ship-order" class="swal2-confirm swal2-styled" style="background-color: #3085d6;">Ship</button>
                <button id="cancel-order" class="swal2-cancel swal2-styled" style="background-color: #aaa; margin-left: 10px;">Cancel</button>
            </div>`;

            Swal.fire({
                title: 'Ship With Sideup',
                html: popupContent,
                showConfirmButton: false,
                width: '80%'
            });

$('#ship-order').on('click', function() {
    let paymentMethod = $(`select[name="paymentMethod_${orderId}"]`).val();
    let carrier = $(`input[name="carrier_${orderId}"]:checked`).val();
    let shipmentCode = (Math.floor(1000000 + Math.random() * 9000000)).toString();
    let phoneNumber = orderData.phone ? orderData.phone : $('.phone-input').val();

    if (!carrier) {
        Swal.fire('Error', 'Please select a carrier before proceeding.', 'error');
        return;
    }

    if (!phoneNumber || phoneNumber.trim() === '') {
        Swal.fire('Error', 'Phone number is missing. Please provide a valid phone number.', 'error');
        return;
    }


    let shipmentData = {
        shipment_code: shipmentCode,
        name: orderData.customer_name,
        phone: phoneNumber,
        area_id: orderData.state,
        address: orderData.address,
        item_description: 'WooCommerce Order',
        courier: carrier,
        landmark: 'N/A',
        notes: 'WooCommerce Order',
        isWooCommerce: true,
        woocommerce_id: orderData.order_key,
        zero_cash_collection: paymentMethod == 3 ? 1 : 0,
        online_payment: paymentMethod == 1 ? 'online_payment' : (paymentMethod == 2 ? 'fawry_payment' : null),
        total_cash_collection: paymentMethod == 3 ? 0 : orderData.total_cost,
        backup_mobile: ''
    };

    // إرسال البيانات كـ Form Data إلى الـ API
    $.ajax({
        url: 'https://portal.eg.sideup.co/api/orders',
        method: 'POST',
        headers: {
            'Authorization': `${token}`
        },
        data: shipmentData, // يتم إرسال البيانات بدون JSON.stringify
        contentType: 'application/x-www-form-urlencoded', // نوع البيانات المُرسلة
        success: function(response) {
            Swal.fire('Success', 'Your order has been shipped.', 'success').then(() => {
                location.reload(); // إعادة تحميل الصفحة بعد النجاح
            });
        },
        error: function(error) {
            Swal.fire('Error', 'Failed to ship order.', 'error');
        }
    });
});


            $('#cancel-order').on('click', function() {
                Swal.close(); // إغلاق الـ popup عند إلغاء الطلب
            });
        },
        error: function() {
            Swal.close();
            Swal.fire('Error', 'Failed to retrieve local prices.', 'error');
        }
});

   
});


});


    $(document).on('change', 'input[type=radio][name=paymentWay]', function() {
        id = $(this).attr('target');
        $(`.payment-way`).val($(this).val());
        row = $(`#${$(this).attr('target')}`);
        item_cost = Number($("#item-cost").val());
        base_fedex_fees = $(".base-fedex-fees").val();
        base_fetchr_fees = $(".base-fetchr-fees").val();
        base_jt_fees = $(".base-jt-fees").val();
        base_aramex_fees = $(".base-aramex-fees").val();
        base_mylerz_fees = $(".base-mylerz-fees").val();
        console.log($(this).val(), 'HELLO', item_cost);
        if($(this).val() == '4') {
            $(".fedex-total-fees").val(((item_cost) > 2500) ?  Number(base_fedex_fees) + Math.ceil((((Number(base_fedex_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_fedex_fees));
            $(".fetchr-total-fees").val(((item_cost) > 2500) ?  Number(base_fetchr_fees) + Math.ceil((((Number(base_fetchr_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_fetchr_fees));
            $(".jt-total-fees").val(((item_cost) > 2500) ?  Number(base_jt_fees) + Math.ceil((((Number(base_jt_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_jt_fees));
            $(".aramex-total-fees").val(((item_cost) > 2500) ?  Number(base_aramex_fees) + Math.ceil((((Number(base_aramex_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_aramex_fees));
            $(".mylerz-total-fees").val(((item_cost) > 2500) ?  Number(base_mylerz_fees) + Math.ceil((((Number(base_mylerz_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_mylerz_fees));
        } else if($(this).val() == '1') {
            $(".fedex-total-fees").val(Math.ceil((Number(base_fedex_fees)) + (0.03 * (item_cost))));
            $(".fetchr-total-fees").val(Math.ceil((Number(base_fetchr_fees)) + (0.03 * (item_cost))));
            $(".jt-total-fees").val(Math.ceil((Number(base_jt_fees)) + (0.03 * (item_cost))));
            $(".aramex-total-fees").val(Math.ceil((Number(base_aramex_fees)) + (0.03 * (item_cost))));
            $(".mylerz-total-fees").val(Math.ceil((Number(base_mylerz_fees)) + (0.03 * (item_cost))));
        } else if($(this).val() == '2') {
            $(".fedex-total-fees").val(Math.ceil((Number(base_fedex_fees)) + (0.03 * (item_cost))));
            $(".fetchr-total-fees").val(Math.ceil((Number(base_fetchr_fees)) + (0.03 * (item_cost))));
            $(".jt-total-fees").val(Math.ceil((Number(base_jt_fees)) + (0.03 * (item_cost))));
            $(".aramex-total-fees").val(Math.ceil((Number(base_aramex_fees)) + (0.03 * (item_cost))));
            $(".mylerz-total-fees").val(Math.ceil((Number(base_mylerz_fees)) + (0.03 * (item_cost))));
        } else if($(this).val() == '3') {
            $(".fedex-total-fees").val(base_fedex_fees);
            $(".fetchr-total-fees").val(base_fetchr_fees);
            $(".jt-total-fees").val(base_jt_fees);
            $(".aramex-total-fees").val(base_aramex_fees);
            $(".mylerz-total-fees").val(base_mylerz_fees);
            $("#item-cost").val(0);
        } else {
            return;
        }
    });

    $(document).on('keyup', '#backup-mobile', function() {
        id = $(this).attr('target');
        // $(`.payment-way`).val();
        row = $(`#${id}`);
        row.attr('data-backup-phone', $(this).val());
    });

    $(document).on('change', '#item-cost', function() {
        id = $(this).attr('target');
        // $(`.payment-way`).val();
        row = $(`#${id}`);
        console.log(id, row);
        // target = $(`#${row}`);
        item_cost = Number($(this).val());
        row.attr('data-total', item_cost);
        console.log(row);
        base_fedex_fees = $(".base-fedex-fees").val();
        base_fetchr_fees = $(".base-fetchr-fees").val();
        base_jt_fees = $(".base-jt-fees").val();
        base_aramex_fees = $(".base-aramex-fees").val();
        base_mylerz_fees = $(".base-mylerz-fees").val();
        console.log($(this).val(), 'HELLO', item_cost, $('input[type=radio][name="paymentWay"]:checked').val());
        if($('input[type=radio][name="paymentWay"]:checked').val() == '4') {
            $(".fedex-total-fees").val(((item_cost) > 2500) ?  Number(base_fedex_fees) + Math.ceil((((Number(base_fedex_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_fedex_fees));
            $(".fetchr-total-fees").val(((item_cost) > 2500) ?  Number(base_fetchr_fees) + Math.ceil((((Number(base_fetchr_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_fetchr_fees));
            $(".jt-total-fees").val(((item_cost) > 2500) ?  Number(base_jt_fees) + Math.ceil((((Number(base_jt_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_jt_fees));
            $(".aramex-total-fees").val(((item_cost) > 2500) ?  Number(base_aramex_fees) + Math.ceil((((Number(base_aramex_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_aramex_fees));
            $(".mylerz-total-fees").val(((item_cost) > 2500) ?  Number(base_mylerz_fees) + Math.ceil((((Number(base_mylerz_fees) + item_cost) - 2500)) * 7 / 1000) : Number(base_mylerz_fees));
        } else if($(`.payment-way`).val() == '1') {
            $(".fedex-total-fees").val(Math.ceil((Number(base_fedex_fees)) + (0.03 * (item_cost))));
            $(".fetchr-total-fees").val(Math.ceil((Number(base_fetchr_fees)) + (0.03 * (item_cost))));
            $(".jt-total-fees").val(Math.ceil((Number(base_jt_fees)) + (0.03 * (item_cost))));
            $(".aramex-total-fees").val(Math.ceil((Number(base_aramex_fees)) + (0.03 * (item_cost))));
            $(".mylerz-total-fees").val(Math.ceil((Number(base_mylerz_fees)) + (0.03 * (item_cost))));
        } else if($(`.payment-way`).val() == '2') {
            $(".fedex-total-fees").val(Math.ceil((Number(base_fedex_fees)) + (0.03 * (item_cost))));
            $(".fetchr-total-fees").val(Math.ceil((Number(base_fetchr_fees)) + (0.03 * (item_cost))));
            $(".jt-total-fees").val(Math.ceil((Number(base_jt_fees)) + (0.03 * (item_cost))));
            $(".aramex-total-fees").val(Math.ceil((Number(base_aramex_fees)) + (0.03 * (item_cost))));
            $(".mylerz-total-fees").val(Math.ceil((Number(base_mylerz_fees)) + (0.03 * (item_cost))));
        } else if($(`.payment-way`).val() == '3') {
            $(".fedex-total-fees").val(base_fedex_fees);
            $(".fetchr-total-fees").val(base_fetchr_fees);
            $(".jt-total-fees").val(base_jt_fees);
            $(".aramex-total-fees").val(base_aramex_fees);
            $(".mylerz-total-fees").val(base_mylerz_fees);
            $("#item-cost").val(0);
        } else {
            return;
        }
    });

});

jQuery(document).ready(function($) {
    let token = sideupSettings.bearerToken; 

    $('#posts-filter').off('submit.bulkShipping');

    $('#posts-filter').on('submit.bulkShipping', function(event) {
        let bulkAction = $('select[name="action"]').val();

        if (bulkAction === 'bulk_ship_orders') {
            event.preventDefault();

            let selectedOrders = [];
            let queryString = '';
            let missingAddressOrders = [];
            let alreadyShippedOrders = [];


            Swal.fire({
                title: 'Please wait',
                text: 'Fetching local prices...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            $('input[name="post[]"]:checked').each(function(index) {
                let orderId = $(this).val();
                let orderData = sideupOrdersData.find(order => order.orderId == orderId);
                // Check if the order already has a "Cancel" button (i.e., it's already shipped)
                let cancelButton = $(`tr#post-${orderId} .cancel-button`);
                if (cancelButton.length > 0) {
                    alreadyShippedOrders.push(orderId);
                    return; // Skip this order if it's already shipped
                }


                if (orderData) {
                    let dropAreaName = orderData.state;

                    // Check if dropAreaName (address) is missing
                    if (!dropAreaName || dropAreaName.trim() === '') {
                        missingAddressOrders.push(orderId);
                    }

                    selectedOrders.push({
                        order_id: orderData.orderId,
                        customer_name: orderData.customer_name,
                        phone: orderData.phone,
                        total_cost: orderData.total_cost,
                        drop_area_name: dropAreaName,
                        address: orderData.address
                    });

                    queryString += `orders[${index}][order_id]=${orderData.orderId}&orders[${index}][drop_area_name]=${encodeURIComponent(dropAreaName)}&`;
                }
            });

            // If any orders are missing addresses, show alert and stop execution
            if (missingAddressOrders.length > 0) {
                Swal.fire('Error', `Please make sure the following orders have an address: ${missingAddressOrders.join(', ')}`, 'error');
                return; // Stop further execution
            }

            // If any orders are already shipped (have the Cancel button), show alert and stop execution
            if (alreadyShippedOrders.length > 0) {
                Swal.fire('Error', `You can't select the following orders for shipping as they are already shipped: ${alreadyShippedOrders.join(', ')}`, 'error');
                return; // Stop further execution
            }
            
            

            queryString = queryString.slice(0, -1);
            let apiUrl = `https://portal.eg.sideup.co/api/localPrices?${queryString}`;

            $.ajax({
                url: apiUrl,
                method: 'GET',
                headers: {
                    'Authorization': `${token}`
                },
                success: function(response) {
                    Swal.close();

                    let popupContent = `
                    <style>
                        .custom-table {
                            width: 100%;
                            border-collapse: collapse;
                        }
                        .custom-table th, .custom-table td {
                            border: 1px solid #ddd;
                            padding: 5px;
                            text-align: center;
                            font-size: 12px;
                        }
                        .custom-table th {
                            background-color: #f2f2f2;
                            color: #333;
                            font-weight: bold;
                        }
                        .custom-table tr:nth-child(even) {
                            background-color: #f9f9f9;
                        }
                        .custom-table tr:hover {
                            background-color: #f1f1f1;
                        }
                        .custom-table td.select-payment-method {
                            width: 150px;
                        }
                        .custom-table td.carriers-options {
                            text-align: left;
                            white-space: normal; /* السماح بتفصيل الأسطر */
                        }
                    </style>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer Name</th>
                                <th>Phone</th>
                                <th>Payment Method</th>
                                <th>Total Cost</th>
                                <th>Carriers</th>
                            </tr>
                        </thead>
                        <tbody>`;

                    selectedOrders.forEach(function(order, index) {
                        let carriers = response.data[order.order_id].prices;
                        let carriersOptions = '';
                        let carrierCount = 0;
                        let carrierLine = '';

                        Object.keys(carriers).forEach(carrier => {
                            let price = carriers[carrier];
                            carrierLine += `
                                <div style="display:inline-block; margin-right: 10px;">
                                    <input type="radio" name="carrier_${order.order_id}" value="${carrier}" id="carrier_${order.order_id}_${carrier}">
                                    <label for="carrier_${order.order_id}_${carrier}">${carrier} - ${price} EGP</label>
                                </div>
                            `;
                            carrierCount++;

                            // إذا وصلنا إلى 3 شركات، نضع الأسطر في carrierOptions ونبدأ سطرًا جديدًا
                            if (carrierCount % 3 === 0) {
                                carriersOptions += `<div style="margin-bottom: 10px;">${carrierLine}</div>`;
                                carrierLine = ''; // نبدأ سطرًا جديدًا
                            }
                        });

                        // إضافة أي شركات متبقية لم يتم وضعها بعد
                        if (carrierLine) {
                            carriersOptions += `<div style="margin-bottom: 10px;">${carrierLine}</div>`;
                        }

                        popupContent += `
                            <tr>
                                <td>${order.order_id}</td>
                                <td>${order.customer_name}</td>
                                <td><input type="text" class="phone-input" value="${order.phone}" /></td>
                                <td class="select-payment-method">
                                    <select class="dropdown-select" name="paymentMethod[${index}]">
                                        <option value="4">Cash On Delivery</option>
                                        <option value="1">Credit Card (Visa or Mastercard)</option>
                                        <option value="2">Fawry</option>
                                        <option value="3">Zero Cash Collection</option>
                                    </select>
                                </td>
                                <td>${order.total_cost}</td>
                                <td class="carriers-options">${carriersOptions}</td>
                            </tr>`;
                    });

                    popupContent += `
                        </tbody>
                    </table>
                    <div style="text-align: center; margin-top: 20px;">
                        <button id="ship-orders" class="swal2-confirm swal2-styled" style="background-color: #3085d6;">Ship</button>
                        <button id="cancel-orders" class="swal2-cancel swal2-styled" style="background-color: #aaa; margin-left: 10px;">Cancel</button>
                    </div>`;

                    Swal.fire({
                        title: 'Ship With Sideup',
                        html: popupContent,
                        showConfirmButton: false,
                        width: '80%'
                    });

                    $('#ship-orders').on('click', function() {
                        let shipments = [];

                        selectedOrders.forEach(function(order) {
                            let paymentMethod = $(`select[name="paymentMethod[${order.order_id}]"]`).val();
                            let carrier = $(`input[name="carrier_${order.order_id}"]:checked`).val();
                            let shipmentCode = Math.floor(1000000 + Math.random() * 9000000).toString(); // تحويل الكود إلى نص
                            let phoneNumber = order.phone ? order.phone : $(`.phone-input[value="${order.phone}"]`).val();


                                    if (!phoneNumber || phoneNumber.trim() === '') {
                                        Swal.fire('Error', `Phone number is missing for order ${order.order_id}. Please provide a valid phone number.`, 'error');
                                        return;
                                    }

                            let shipmentData = {
                                shipment_code: shipmentCode,
                                name: order.customer_name,
                                phone: phoneNumber,
                                area_id: order.drop_area_name,
                                address: order.address,
                                item_description: "WooCommerce Order",
                                courier: carrier,
                                woocommerce_id: order.order_key,
                                landmark: 'N/A',
                                notes: 'WooCommerce Order',
                                isWooCommerce: true,
                                zero_cash_collection: paymentMethod == 3 ? 1 : 0,
                                online_payment: paymentMethod == 1 ? null : (paymentMethod == 2 ? 'fawry_payment' : null),
                                total_cash_collection: paymentMethod == 3 ? "0.00" : order.total_cost.toString(), // تحويل القيمة إلى نص
                                backup_mobile: ''
                            };

                            shipments.push(shipmentData);
                        });

                        let requestData = {
                            orders: shipments
                        };

                        console.log('Request Data:', JSON.stringify(requestData, null, 2));

                        $.ajax({
                            url: 'https://portal.eg.sideup.co/api/ordersWordpressBulk',
                            method: 'POST',
                            headers: {
                                'Authorization': `${token}`
                            },
                            data: requestData, // يتم إرسال البيانات بدون JSON.stringify
                            contentType: 'application/x-www-form-urlencoded', // نوع البيانات المُرسلة
                            success: function(response) {
                                Swal.fire('Success', 'Your orders have been shipped.', 'success');
                            },
                            error: function(error) {
                                Swal.fire('Error', 'Failed to ship orders.', 'error');
                            }
                        });
                    });

                    $('#cancel-orders').on('click', function() {
                        Swal.close();
                    });
                },
                error: function() {
                    Swal.close();
                    Swal.fire('Error', 'Failed to retrieve local prices.', 'error');
                }
            });
        }
    });
});


