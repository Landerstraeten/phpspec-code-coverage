<?php

namespace spec\LeanPHP\PhpSpec\CodeCoverage\Listener;

use PhpSpec\Console\ConsoleIO;
use PhpSpec\Event\SuiteEvent;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report;

/**
 * @author Henrik Bjornskov
 */
class CodeCoverageListenerSpec extends ObjectBehavior
{
    function let(ConsoleIO $io, CodeCoverage $coverage)
    {
        $this->beConstructedWith($io, $coverage, []);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LeanPHP\PhpSpec\CodeCoverage\Listener\CodeCoverageListener');
    }

    function it_should_run_all_reports(
        CodeCoverage $coverage,
        Report\Html\Facade $html,
        Report\Clover $clover,
        Report\PHP $php,
        Report\Xml\Facade $xml,
        SuiteEvent $event,
        ConsoleIO $io
    ) {
        $reports = [
            'html' => $html,
            'clover' => $clover,
            'xml' => $xml,
            'php' =>  $php,
        ];

        $io->isVerbose()->willReturn(false);

        $this->beConstructedWith($io, $coverage, $reports);
        $this->setOptions([
            'format' => [
                'text',
                'html',
                'clover',
                'php',
                'xml',
            ],
            'output' => [
                'html' => 'coverage',
                'clover' => 'coverage.xml',
                'php' => 'coverage.php',
                'xml' => 'coverage',
            ],
        ]);

        $clover->process($coverage, 'coverage.xml')->shouldBeCalled();
        $html->process($coverage, 'coverage')->shouldBeCalled();
        $php->process($coverage, 'coverage.php')->shouldBeCalled();
        $xml->process($coverage, 'coverage')->shouldBeCalled();

        $this->afterSuite($event);
    }

    function it_should_color_output_text_report_by_default(
        CodeCoverage $coverage,
        Report\Text $text,
        SuiteEvent $event,
        ConsoleIO $io
    ) {
        $reports = [
            'text' => $text,
        ];

        $this->beConstructedWith($io, $coverage, $reports);
        $this->setOptions([
            'format' => 'text',
        ]);

        $io->isVerbose()->willReturn(false);
        $io->isDecorated()->willReturn(true);

        $text->process($coverage, true)->willReturn('report');
        $io->writeln('report')->shouldBeCalled();

        $this->afterSuite($event);
    }

    function it_should_not_color_output_text_report_unless_specified(
        CodeCoverage $coverage,
        Report\Text $text,
        SuiteEvent $event,
        ConsoleIO $io
    ) {
        $reports = [
            'text' => $text,
        ];

        $this->beConstructedWith($io, $coverage, $reports);
        $this->setOptions([
            'format' => 'text',
        ]);

        $io->isVerbose()->willReturn(false);
        $io->isDecorated()->willReturn(false);

        $text->process($coverage, false)->willReturn('report');
        $io->writeln('report')->shouldBeCalled();

        $this->afterSuite($event);
    }

    function it_should_output_html_report(
        CodeCoverage $coverage,
        Report\Html\Facade $html,
        SuiteEvent $event,
        ConsoleIO $io
    ) {
        $reports = [
            'html' => $html,
        ];

        $this->beConstructedWith($io, $coverage, $reports);
        $this->setOptions([
            'format' => 'html',
            'output' => [
                'html' => 'coverage',
            ],
        ]);

        $io->isVerbose()->willReturn(false);
        $io->writeln(Argument::any())->shouldNotBeCalled();


        $html->process($coverage, 'coverage')->willReturn('report');

        $this->afterSuite($event);
    }

    function it_should_provide_extra_output_in_verbose_mode(
        CodeCoverage $coverage,
        Report\Html\Facade $html,
        SuiteEvent $event,
        ConsoleIO $io
    ) {
        $reports = [
            'html' => $html,
        ];

        $this->beConstructedWith($io, $coverage, $reports);
        $this->setOptions([
            'format' => 'html',
            'output' => [
                'html' => 'coverage',
            ],
        ]);

        $io->isVerbose()->willReturn(true);
        $io->writeln('')->shouldBeCalled();
        $io->writeln('Generating code coverage report in html format ...')->shouldBeCalled();

        $this->afterSuite($event);
    }

    function it_should_correctly_handle_black_listed_files_and_directories(
        CodeCoverage $coverage,
        SuiteEvent $event,
        Filter $filter,
        ConsoleIO $io
    )
    {
        $this->beConstructedWith($io, $coverage, []);

        $coverage->filter()->willReturn($filter);

        $this->setOptions([
            'whitelist' => ['src'],
            'blacklist' => ['src/filter'],
            'whitelist_files' => ['src/filter/whilelisted_file'],
            'blacklist_files' => ['src/filtered_file'],
        ]);

        $filter->addDirectoryToWhitelist('src')->shouldBeCalled();
        $filter->removeDirectoryFromWhitelist('src/filter')->shouldBeCalled();
        $filter->addFileToWhitelist('src/filter/whilelisted_file')->shouldBeCalled();
        $filter->removeFileFromWhitelist('src/filtered_file')->shouldBeCalled();

        $this->beforeSuite($event);
    }
}
