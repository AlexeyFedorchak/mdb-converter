<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GeneralEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * email's text
     *
     * @var string
     */
    protected $text;

    /**
     * email's subject
     *
     * @var string
     */
    protected $subjectValue;

    /**
     * Create a new message instance.
     *
     * GeneralEmail constructor.
     * @param string $text
     * @param string $subjectValue
     */
    public function __construct(string $text, string $subjectValue)
    {
        $this->text = $text;
        $this->subjectValue = $subjectValue;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('general-email')
            ->subject($this->subjectValue)
            ->with([
                'text' => $this->text,
            ]);
    }
}
