<?php

declare(strict_types=1);

namespace App\Actions\Outreach;

use App\Models\Company;
use App\Models\OutreachEmail;
use App\Models\People;
use App\Support\MailQuota;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;

/**
 * Builds the Fin Mail message for one recipient of an outreach email.
 *
 * Personalisation values are passed as escaped strings rather than models, so a name containing HTML
 * cannot change the email's markup, and contact tags render empty for a company's general email.
 */
final readonly class BuildOutreachMail
{
    /**
     * Merge tags staff can use in the subject and body, with labels for the editor.
     *
     * @var array<string, string>
     */
    public const array MERGE_TAGS = [
        'recipient.name' => 'Recipient name (contact, or company for general emails)',
        'company.name' => 'Company name',
        'contact.first_name' => 'Contact first name',
        'contact.last_name' => 'Contact last name',
        'contact.job_title' => 'Contact role',
    ];

    /**
     * @param  string|null  $unsubscribeUrl  Null for test sends, which show a placeholder instead.
     */
    public function handle(OutreachEmail $email, Company $company, ?People $contact, string $name, ?string $unsubscribeUrl): TemplateMail
    {
        $templateKey = filled($email->template_key) && EmailTemplate::findByKey($email->template_key) !== null
            ? $email->template_key
            : (string) config('outreach.template_key');

        $mail = TemplateMail::make($templateKey)
            ->models([
                'recipient' => ['name' => e($name)],
                'company' => ['name' => e($company->name)],
                'contact' => [
                    'first_name' => e($contact->first_name ?? ''),
                    'last_name' => e($contact->last_name ?? ''),
                    'job_title' => e($contact->job_title ?? ''),
                ],
            ])
            ->overrideSubject($this->replaceSubjectTokens($email->subject, $company, $contact, $name))
            ->overrideBody($email->body.$this->footer($company, $unsubscribeUrl))
            ->withSymfonyMessage(function (Email $message) use ($email, $unsubscribeUrl): void {
                $headers = $message->getHeaders();
                $headers->addTextHeader(MailQuota::OUTREACH_HEADER, (string) $email->getKey());

                if ($unsubscribeUrl !== null) {
                    $headers->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
                    $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                }
            });

        if (filled($replyTo = config('outreach.reply_to'))) {
            $mail->overrideReplyTo((string) $replyTo);
        }

        $disk = Storage::disk((string) config('fin-mail.attachments_disk', 'local'));

        foreach ($email->attachments ?? [] as $path) {
            if ($disk->exists($path)) {
                $mail->attachFile($disk->path($path), $email->attachment_names[$path] ?? basename($path));
            }
        }

        return $mail;
    }

    /**
     * Fin Mail replaces tokens in the subject only from the template, so an overridden subject is personalised here.
     */
    private function replaceSubjectTokens(string $subject, Company $company, ?People $contact, string $name): string
    {
        $values = [
            'recipient.name' => $name,
            'company.name' => $company->name,
            'contact.first_name' => $contact->first_name ?? '',
            'contact.last_name' => $contact->last_name ?? '',
            'contact.job_title' => $contact->job_title ?? '',
        ];

        return (string) preg_replace_callback(
            '/\{\{\s*([\w.]+)\s*\}\}/',
            fn (array $matches): string => $values[$matches[1]] ?? $matches[0],
            $subject,
        );
    }

    private function footer(Company $company, ?string $unsubscribeUrl): string
    {
        $link = $unsubscribeUrl === null
            ? '<span>Unsubscribe (link added when sent)</span>'
            : '<a href="'.e($unsubscribeUrl).'">Unsubscribe</a>';

        return '<hr><p style="font-size:12px;color:#6b7280;">You are receiving this email because '.e($company->name)
            .' is in Spinney Hill Support Centre\'s outreach directory. '.$link.'</p>';
    }
}
