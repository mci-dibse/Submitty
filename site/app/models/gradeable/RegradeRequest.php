<?php

namespace app\models\gradeable;


use app\libraries\Core;
use app\libraries\DateUtils;
use app\models\AbstractModel;

/**
 * Class RegradeRequest
 * @package app\models\gradeable
 *
 * Loads regrade request status and timestamp on construction
 *  and TODO lazy-loads regrade discussion info
 *
 * @method int getStatus()
 * @method \DateTime getTimestamp()
 */
class RegradeRequest extends AbstractModel {

    /** @var TaGradedGradeable Reference to the grade this regrade request is for */
    private $ta_graded_gradeable = null;
    /** @property @var int Status of the regrade request */
    protected $status = 0;
    /** @property @var \DateTime TODO */
    protected $timestamp = null;

    const REGRADE_STATUS_TBD = -1;
    const REGRADE_STATUS_TBD1 = 0;
    const REGRADE_STATUS_TBD2 = 1;

    /**
     * RegradeRequest constructor.
     * @param Core $core
     * @param TaGradedGradeable $ta_graded_gradeable
     * @param array $details
     * @throws \InvalidArgumentException If the timestamp is null or invalid, if the status is invalid, or if the
     *              TaGradedGradeable is null
     */
    public function __construct(Core $core, TaGradedGradeable $ta_graded_gradeable, array $details) {
        parent::__construct($core);

        if($ta_graded_gradeable === null) {
            throw new \InvalidArgumentException('TaGradedGradeable instance cannot be null');
        }
        $this->ta_graded_gradeable = $ta_graded_gradeable;
        $this->setTimestamp($details['timestamp']);
        $this->setStatus($details['status']);
        $this->modified = false;
    }

    public function toArray() {
        $details = parent::toArray();

        // Make sure to convert the date into a string
        $details['timestamp'] = DateUtils::dateTimeToString($this->timestamp);

        return $details;
    }

    /**
     * Gets the gradeable this regrade request is associated with
     * @return Gradeable
     */
    public function getGradeable() {
        return $this->ta_graded_gradeable->getGradedGradeable()->getGradeable();
    }

    /**
     * Gets the submitter this regrade request is for
     * @return Submitter
     */
    public function getSubmitter() {
        return $this->ta_graded_gradeable->getGradedGradeable()->getSubmitter();
    }

    /**
     * Set the status of the regrade request (see statuses above)
     * @param $status
     */
    public function setStatus($status) {
        if(!in_array($status, [self::REGRADE_STATUS_TBD, self::REGRADE_STATUS_TBD1, self::REGRADE_STATUS_TBD2])) {
            throw new \InvalidArgumentException('Invalid regrade request status');
        }
        $this->status = $status;
        $this->modified = true;
    }

    /**
     * Sets the TODO:
     * @param \DateTime|string $timestamp
     */
    public function setTimestamp($timestamp) {
        if ($timestamp === null) {
            throw new \InvalidArgumentException('Regrade request timestamp cannot be null');
        } else {
            try {
                $this->timestamp = DateUtils::parseDateTime($timestamp, $this->core->getConfig()->getTimezone());
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid date string format');
            }
        }
        $this->modified = true;
    }
}