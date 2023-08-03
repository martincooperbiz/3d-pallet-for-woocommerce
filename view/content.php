<?php
/**
 * @var WP_Post $wpPost
 * @var WC_Order $order
 */

$order = wc_get_order( $wpPost->ID );
?>
<link rel="stylesheet" href="<?php echo URL_3D_PALLET_DIR_CSS;?>main.css">
<link rel="stylesheet" href="<?php echo URL_3D_PALLET_DIR_CSS;?>custom.css">
<div class="h-full p-2 flex flex-column border-solid border-2 border-indigo-600" style="display: none;" id="result-row"></div>
<div class="h-full p-2 flex flex-row">
    <div class="h-full flex flex-col mr-2 space-y-2">
        <label>Depth:(sm)<br>
            <input class="grow border-solid p-1 border-2 border-indigo-600" id="depth" value="100">
        </label>
    </div>
    <div  class="h-full flex flex-col mr-2 space-y-2">
        <label>Height:(sm)<br>
            <input class="grow border-solid p-1 border-2 border-indigo-600" id="height" value="220">
        </label>
    </div>
    <div  class="h-full flex flex-col mr-2 space-y-2">
        <label>Width:(sm)<br>
            <input class="grow border-solid p-1 border-2 border-indigo-600" id="width" value="120">
        </label>
    </div>
    <div  class="h-full flex flex-col mr-2 space-y-2">&nbsp;
        <a class="p-1 w-full bg-violet-500 hover:bg-violet-600 active:bg-violet-700 focus:outline-none focus:ring focus:ring-violet-300 text-white" href="javascript:void(0);" onclick="drawData();" >Apply</a>
    </div>
    <div  class="h-full flex flex-col mr-2 space-y-2">
        &nbsp;
        <a class="p-1 w-full bg-violet-500 hover:bg-violet-600 active:bg-violet-700 focus:outline-none focus:ring focus:ring-violet-300 text-white" href="javascript:void(0);" onclick="autoScaleDrawData()">Auto Scale</a>
    </div>
</div>
<div id="auto-scale-loading" style='display:none;background-image: url("/wp-includes/js/thickbox/loadingAnimation.gif");height: 26px;background-repeat: no-repeat;background-position: center;' ></div>
<div id="visualize-3d-pallet-root">Loading.</div>
<script src="<?php echo URL_3D_PALLET_DIR_JS; ?>main.js" ></script>
<script>
    const autoScaleLoading = document.querySelector('#auto-scale-loading');
    const products = [];

    <?php
    $productInfos = [];
    foreach ($order->get_items() as $item_id => $item) {
        $order_item_data = $item->get_data(); // Get WooCommerce order item meta data in an unprotected array
        if ((int)$order_item_data['variation_id']) {
            $product_object = wc_get_product($order_item_data['variation_id']);
            $productInfos[] = [
                'name' => $order_item_data['name'],
                'w' => $product_object->get_width(),
                'd' => $product_object->get_length(),
                'h' => $product_object->get_height(),
                'q' => $order_item_data['quantity']
            ];
        }
    }

    ?>

    products.push(...JSON.parse('<?php echo json_encode($productInfos);?>'));

    function delay(time) {
        return new Promise(resolve => setTimeout(resolve, time));
    }

    function drawData() {
        const depth = parseInt(document.querySelector('#depth').value)||100;
        const width = parseInt(document.querySelector('#width').value)||120;
        const height = parseInt(document.querySelector('#height').value)||250;
        let data = `${depth}, ${height}, ${width}`;
        products.forEach((product, i)=>{
            data += `\n${i}. ${product.d}, ${product.h}, ${product.w}, ${product.q}`;
        });
        window.lotus.c(window.lotus.Jb(window.lotus.vn.F(data)))
        delay(500).then(()=>{
            console.info('data:', data)
            console.info('products:', products)
            console.info('result:', window.lotus.result)
            applyResult(window.lotus.result);

        })

    }

    function applyResult(result) {
        const elInfoBlock = document.querySelector('#result-row');
        elInfoBlock.innerHTML = '';
        result.forEach((item, idx)=>{
            if (!item || idx < 1) {
                return;
            }
            if (typeof item === "string") {
                const [name, value] = item.split(':');
                elInfoBlock.innerHTML += `<p><strong>${name}:</strong>${value}</p>`;
            }
            if (typeof item === "object") {

                elInfoBlock.innerHTML += `<p><strong>${item[0]}</strong></p>`;
                const unpackedBoxes = [];
                item.slice(1).forEach(unpacked=>{
                    const id = parseInt(unpacked.substr(0, 2));
                    if (unpackedBoxes.indexOf(id) === -1) {
                        const name = products[id].name;
                        const size = unpacked.substr(2);
                        unpackedBoxes.push(id);
                        elInfoBlock.innerHTML += `<p>&nbsp&nbsp&nbsp<strong>${name}:</strong>${size}</p>`;
                    }
                })
            }
            elInfoBlock.style.display = 'block';
        });
    }

    document.addEventListener('DOMContentLoaded', function (){
        drawData();
    });


    var isRunTime = false;
    var isBackSize = false;
    var intervalId = 0;
    const results = [];
    var lastSend = '';
    var diffWidth = 0;
    var sendWidth = 0;
    var diffDepth = 0;
    var sendDepth = 0;
    var diffHeight = 0;
    var sendHeight = 0;

    var packedBoxes = 0;


    var step = 1;
    var minWidth = 0;
    function autoScaleDrawData() {
        clearInterval(intervalId);
        autoScaleLoading.style.display = 'block';
        console.info('Auto scale start.')
        diffWidth = 0;
        sendWidth = 0;
        diffDepth = 0;
        sendDepth = 0;
        diffHeight = 0;
        sendHeight = 0;
        minWidth = 0;
        packedBoxes = 0;
        step = 1;
        isBackSize = false;


        let depth = parseInt(document.querySelector('#depth').value)||100;
        let width = parseInt(document.querySelector('#width').value)||120;
        let height = parseInt(document.querySelector('#height').value)||220;

        let data = '';
        products.forEach((product, i)=>{
            data += `\n${i}. ${product.d}, ${product.h}, ${product.w}, ${product.q}`;
            if (minWidth < product.w) {
                minWidth = product.w;
            }
        });

        intervalId = setInterval(() => {

            if (isRunTime) {
                return;
            }
            isRunTime = true;



            if (window.lotus.result) {
                const result = window.lotus.result;
                window.lotus.result = null;
                const resultData = {
                    result,
                    size: result[1],
                    volume: result[2].split(': ')[1],
                    useVolume: result[3].split(': ')[1],
                    lastSend,
                };


                const packed = parseInt(result[4].split(': ')[1]);
                if (step===1) {
                    if (!isBackSize&&packedBoxes <= packed) {
                        console.info('Step:', step)
                        packedBoxes = packed;
                        diffWidth += Math.ceil(sendWidth / 2);
                        diffDepth += Math.ceil(sendDepth / 2);
                        results.push(resultData)
                    } else {
                        isBackSize = true;
                        if (packedBoxes > packed) {
                            console.info('Step backspace size:', step)
                            diffWidth -= Math.ceil(diffWidth / 2);
                            diffDepth -= Math.ceil(diffDepth / 2);
                        }else {
                            packedBoxes = packed;
                            results.push(resultData)
                            console.info('Next step')
                            step++;
                        }
                    }
                    applyResult(result);
                }else {
                    if (step===2) {
                        console.info('Step:', step)
                        if (packedBoxes <= packed) {
                            diffWidth++;
                            diffDepth++;
                            results.push(resultData)
                        } else {
                            console.info('Next step')
                            step++;
                            diffWidth--;
                            diffDepth--;
                        }
                        applyResult(result);
                    }else {
                        if (step===3) {
                            console.info('Step:', step)
                            if (packedBoxes <= packed) {
                                diffDepth++;
                                results.push(resultData)
                            }else {
                                console.info('Next step')
                                step++;
                                diffDepth--;
                            }
                            applyResult(result);
                        }else {
                            if (step===4) {
                                console.info('Step:', step)
                                if (packedBoxes <= packed) {
                                    diffWidth++;
                                    results.push(resultData)
                                }else {
                                    console.info('Next step')
                                    step++;
                                    diffWidth--;
                                }
                                applyResult(result);
                            }else {
                                if (step===5) {
                                    console.info('Step:', step)
                                    if (packedBoxes <= packed) {
                                        diffHeight+= 5;
                                        results.push(resultData)
                                    } else {
                                        console.info('Next step')
                                        step++;
                                        diffHeight-=5;
                                    }
                                    applyResult(result);
                                }else {
                                    if (packedBoxes <= packed) {
                                        console.info('Step:', step)
                                        diffHeight++;
                                        results.push(resultData)
                                        applyResult(result);
                                    } else {
                                        console.info('Next step')
                                        step++;
                                        diffHeight--;
                                        applyResult(result);
                                        autoScaleResult()
                                    }
                                }
                            }
                        }
                    }
                }
            }

            sendWidth = width - diffWidth;
            sendDepth = depth - diffDepth;
            sendHeight = height - diffHeight;



            const sendData = `${sendDepth}, ${sendHeight}, ${sendWidth}` + data;
            window.lotus.c(window.lotus.Jb(window.lotus.vn.F(sendData)));
            lastSend = sendData;
        }, 50)

    }

    function autoScaleResult() {
        clearInterval(intervalId);
        if (results.length) {
            window.lotus.c(window.lotus.Jb(window.lotus.vn.F(results[results.length - 1].lastSend)));
            applyResult(results[results.length - 1].result);
        }
        setTimeout(()=>{
            autoScaleLoading.style.display = 'none';
        }, 100)
        console.info('Last Send:\n', results[results.length - 1].lastSend)
        console.info('Result:')
        console.dir(results)
        console.info('Auto scale end.')
    }

</script>


