<?php
namespace Phwoolcon\Payment\Model;

use Phalcon\Di;
use Phwoolcon\Events;
use Phwoolcon\Payment\Exception\OrderException;
use Phwoolcon\Fsm\StateMachine;

/**
 * Class OrderFsmTrait
 * @package Phwoolcon\Payment\Model
 *
 * @method Order updateStatus(string $status, string $comment)
 */
trait OrderFsmTrait
{
    /**
     * @var StateMachine
     */
    protected $fsm;
    protected $fsmTransitions = [
        Order::STATUS_PENDING => [
            'prepare' => Order::STATUS_PENDING,
            'confirm' => Order::STATUS_PROCESSING,
            'complete' => Order::STATUS_COMPLETE,
            'cancel' => Order::STATUS_CANCELING,
            'fail' => Order::STATUS_FAILING,
        ],
        Order::STATUS_PROCESSING => [
            'complete' => Order::STATUS_COMPLETE,
            'cancel' => Order::STATUS_CANCELING,
            'fail' => Order::STATUS_FAILING,
        ],
        Order::STATUS_CANCELING => [
            'complete' => Order::STATUS_COMPLETE,
            'confirm_cancel' => Order::STATUS_CANCELED,
        ],
        Order::STATUS_FAILING => [
            'complete' => Order::STATUS_COMPLETE,
            'confirm_fail' => Order::STATUS_FAILED,
        ],
    ];

    public function canCancel()
    {
        return $this->getFsm()->canDoAction('cancel');
    }

    public function cancel($comment = null)
    {
        if (!$this->canCancel()) {
            throw new OrderException(__('Can not mark a %status% order as canceling', [
                'status' => $this->getStatus(),
            ]), OrderException::ERROR_CODE_ORDER_CANNOT_BE_CANCELED);
        }
        Events::fire('order:before_canceling', $this);
        $status = $this->getFsm()->doAction('cancel');
        $this->updateStatus($status, $comment ?: __('Order canceling'))
            ->refreshFsmHistory();
        Events::fire('order:after_canceling', $this);
    }

    public function canComplete()
    {
        return $this->getFsm()->canDoAction('complete');
    }

    public function canConfirm()
    {
        return $this->getFsm()->canDoAction('confirm');
    }

    public function canConfirmCancel()
    {
        return $this->getFsm()->canDoAction('confirm_cancel');
    }

    public function canConfirmFail()
    {
        return $this->getFsm()->canDoAction('confirm_fail');
    }

    public function canFail()
    {
        return $this->getFsm()->canDoAction('fail');
    }

    public function canPrepare()
    {
        return $this->getFsm()->canDoAction('prepare');
    }

    public function complete($comment = null)
    {
        if (!$this->canComplete()) {
            throw new OrderException(__('Can not complete a %status% order', [
                'status' => $this->getStatus(),
            ]), OrderException::ERROR_CODE_ORDER_COMPLETED);
        }
        Events::fire('order:before_complete', $this);
        $status = $this->getFsm()->doAction('complete');
        $this->resetCallbackStatus()
            ->setData('completed_at', time())
            ->setData('cash_paid', $this->getData('cash_to_pay'))
            ->setData('cash_to_pay', 0)
            ->updateStatus($status, $comment ?: __('Order complete'))
            ->refreshFsmHistory();
        Events::fire('order:after_complete', $this);
    }

    public function confirm($comment = null)
    {
        if (!$this->canConfirm()) {
            throw new OrderException(__('Can not confirm a %status% order', [
                'status' => $this->getStatus(),
            ]), OrderException::ERROR_CODE_ORDER_PROCESSING);
        }
        Events::fire('order:before_processing', $this);
        $status = $this->getFsm()->doAction('confirm');
        $this->updateStatus($status, $comment ?: __('Order confirmed'))
            ->refreshFsmHistory();
        Events::fire('order:after_processing', $this);
    }

    public function confirmCancel($comment = null)
    {
        if (!$this->canConfirmCancel()) {
            throw new OrderException(__('Can not cancel a %status% order', [
                'status' => $this->getStatus(),
            ]), OrderException::ERROR_CODE_ORDER_CANNOT_BE_CANCELED);
        }
        Events::fire('order:before_canceled', $this);
        $status = $this->getFsm()->doAction('confirm_cancel');
        $this->updateStatus($status, $comment ?: __('Order canceled'))
            ->setData('canceled_at', time())
            ->refreshFsmHistory();
        Events::fire('order:after_canceled', $this);
    }

    public function confirmFail($comment = null)
    {
        if (!$this->canConfirmFail()) {
            throw new OrderException(__('Can not fail a %status% order', [
                'status' => $this->getStatus(),
            ]), OrderException::ERROR_CODE_ORDER_CANNOT_BE_FAILED);
        }
        Events::fire('order:before_failed', $this);
        $status = $this->getFsm()->doAction('confirm_fail');
        $this->updateStatus($status, $comment ?: __('Order failed'))
            ->setData('failed_at', time())
            ->refreshFsmHistory();
        Events::fire('order:after_failed', $this);
    }

    public function fail($comment = null)
    {
        if (!$this->canFail()) {
            throw new OrderException(__('Can not mark a %status% order as failing', [
                'status' => $this->getStatus(),
            ]), OrderException::ERROR_CODE_ORDER_CANNOT_BE_FAILED);
        }
        Events::fire('order:before_failing', $this);
        $status = $this->getFsm()->doAction('fail');
        $this->updateStatus($status, $comment ?: __('Order failing'))
            ->refreshFsmHistory();
        Events::fire('order:after_failing', $this);
    }

    /**
     * @return StateMachine
     */
    public function getFsm()
    {
        if (!$this->fsm) {
            $this->fsm = StateMachine::create($this->fsmTransitions, $this->getFsmHistory());
        }
        return $this->fsm;
    }

    public function getFsmHistory()
    {
        return $this->getOrderData('fsm_history') ?: [];
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getFsmTransitions()
    {
        return $this->fsmTransitions;
    }

    public static function prepare($data)
    {
        /* @var Order $order */
        $order = Di::getDefault()->get(Order::class);

        // Detect required fields
        foreach ($order->requiredFieldsOnPreparation as $field) {
            if (empty($data[$field])) {
                throw new OrderException(__('Missing required field %field%', [
                    'field' => $field,
                ]), OrderException::ERROR_CODE_BAD_PARAMETERS);
            }
        }
        // Load existing order if any
        if ($existingOrder = $order->getByTradeId($data['trade_id'], $data['client_id'])) {
            $order = $existingOrder;
            if (!$order->canPrepare()) {
                throw new OrderException(__('Order "%trade_id%" is %status%, please do not submit repeatedly', [
                    'trade_id' => $data['trade_id'],
                    'status' => $order->getStatus(),
                ]), OrderException::ERROR_CODE_ORDER_PROCESSING);
            }
        }
        $order->setOrderData('request_data', $data);

        // Fire before_prepare_order_data event
        $data = Events::fire('order:before_prepare_order_data', $order, $data) ?: $data;

        // Filter protected fields
        foreach ($order->protectedFieldsOnPreparation as $field) {
            unset($data[$field]);
        }
        unset($data[static::PREFIXED_ORDER_ID_FIELD]);

        // Remove objects in $data
        foreach ($data as $k => $v) {
            // @codeCoverageIgnoreStart
            if (is_object($v)) {
                unset($data[$k]);
            };
            // @codeCoverageIgnoreEnd
        }

        // Verify order data
        $amount = $data['amount'] = fnGet($data, 'amount') * 1;
        if ($amount <= 0) {
            throw new OrderException(__('Invalid order amount'), OrderException::ERROR_CODE_BAD_PARAMETERS);
        }
        $cashToPay = fnGet($data, 'cash_to_pay', $amount);
        if ($cashToPay < 0) {
            throw new OrderException(__('Invalid order cash to pay'), OrderException::ERROR_CODE_BAD_PARAMETERS);
        }
        $data['cash_to_pay'] = $cashToPay;

        // Set order attributes
        $keyFields = $order->getKeyFields();
        foreach ($order->toArray() as $attribute => $oldValue) {
            $newValue = fnGet($data, $attribute);
            if (isset($keyFields[$attribute]) && $oldValue && $oldValue != $newValue) {
                throw new OrderException(
                    __('Order crucial attribute [%attribute%] changed', compact('attribute')),
                    OrderException::ERROR_CODE_KEY_PARAMETERS_CHANGED
                );
            }
            $newValue === null or $order->setData($attribute, $newValue);
        }

        // Fire after_prepare_order_data event
        $data = Events::fire('order:after_prepare_order_data', $order, $data) ?: $data;
        // Generate order id
        $order->generateOrderId(fnGet($data, 'order_prefix'));
        unset($data['order_prefix']);
        $order->setOrderData($data)
            ->updateStatus($order->getFsm()->getCurrentState(), __('Order initialized'))
            ->refreshFsmHistory();
        return $order;
    }

    public function refreshFsmHistory()
    {
        $this->setOrderData('fsm_history', $this->getFsm()->getHistory());
        return $this;
    }

    /**
     * @return Order
     */
    public function resetCallbackStatus()
    {
        $this->setData('callback_status', '');
        return $this;
    }

    /**
     * @param array $fsmTransitions
     * @return $this
     * @codeCoverageIgnore
     */
    public function setFsmTransitions(array $fsmTransitions)
    {
        $this->fsmTransitions = $fsmTransitions;
        return $this;
    }
}
