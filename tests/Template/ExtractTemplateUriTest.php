<?php

namespace Zenstruck\Uri\Tests\Template;

use Zenstruck\Uri;
use Zenstruck\Uri\TemplateUri;
use Zenstruck\Uri\Tests\UriTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExtractTemplateUriTest extends UriTest
{
    /**
     * @test
     */
    public function can_get_template_parameters_and_uri(): void
    {
        $uri = TemplateUri::extract('/repos/{owner}/{repo}', '/repos/kbond/foundry');

        $this->assertSame('/repos/{owner}/{repo}', $uri->template());
        $this->assertSame(['owner' => 'kbond', 'repo' => 'foundry'], $uri->parameters()->all());
        $this->assertSame('/repos/kbond/foundry', $uri->toString());
    }

    /**
     * @test
     */
    public function not_enough_parameters(): void
    {
        $uri = TemplateUri::extract('/repos/{owner}/{repo}', '/repos/kbond');

        $this->assertSame('/repos/{owner}/{repo}', $uri->template());
        $this->assertSame(['owner' => 'kbond'], $uri->parameters()->all());
        $this->assertSame('/repos/kbond', $uri->toString());
    }

    /**
     * @test
     */
    public function complex_template(): void
    {
        $template = '/repos/{owner}/{repo}/contents/{path}{?ref}';
        $uri = TemplateUri::extract($template, '/repos/kbond/foundry/contents/README.md');

        $this->assertSame($template, $uri->template());
        $this->assertSame(['owner' => 'kbond', 'repo' => 'foundry', 'path' => 'README.md'], $uri->parameters()->all());
        $this->assertSame('/repos/kbond/foundry/contents/README.md', $uri->toString());

        $uri = TemplateUri::extract($template, '/repos/kbond/foundry/contents/README.md?ref=1.x');

        $this->assertSame($template, $uri->template());
        $this->assertSame(['owner' => 'kbond', 'repo' => 'foundry', 'path' => 'README.md', 'ref' => '1.x'], $uri->parameters()->all());
        $this->assertSame('/repos/kbond/foundry/contents/README.md?ref=1.x', $uri->toString());

        $uri = TemplateUri::extract($template, '/repos/kbond/foundry/contents/README.md?ref=');

        $this->assertSame($template, $uri->template());
        $this->assertSame(['owner' => 'kbond', 'repo' => 'foundry', 'path' => 'README.md'], $uri->parameters()->all());
        $this->assertSame('/repos/kbond/foundry/contents/README.md?ref=', $uri->toString());
    }

    /**
     * @test
     */
    public function can_add_parameters(): void
    {
        $template = '/orgs/{org}/repos{?type,sort,direction,per_page,page}';

        $uri = TemplateUri::extract($template, '/orgs/kbond/repos');
        $this->assertSame('/orgs/kbond/repos', $uri->toString());

        $uri = $uri->withParameter('sort', 'updated');
        $this->assertSame('/orgs/kbond/repos?sort=updated', $uri->toString());

        $uri = $uri->withParameters(['org' => 'zenstruck', 'type' => 'private', 'sort' => 'created', 'page' => 2]);
        $this->assertSame('/orgs/zenstruck/repos?type=private&sort=created&page=2', $uri->toString());

        $uri = $uri->withoutParameters('type', 'page');
        $this->assertSame('/orgs/zenstruck/repos?sort=created', $uri->toString());

        $uri = $uri->mergeParameters(['type' => 'foo', 'page' => 3]);
        $this->assertSame('/orgs/zenstruck/repos?type=foo&sort=created&page=3', $uri->toString());
    }

    protected function uriFor(string $value): Uri
    {
        return TemplateUri::extract('', $value);
    }
}
