<be-page-content>
    <div class="be-bc-fff be-p-150" id="app" v-cloak>
        <!-- 搜索框 -->
        <div class="be-row be-mb-200">
            <div class="be-col">
                <el-input
                    v-model="keywords"
                    placeholder="请输入搜索关键词"
                    size="large"
                    clearable
                    @keyup.enter.native="doSearch">
                    <el-button slot="append" icon="el-icon-search" @click="doSearch">搜索</el-button>
                </el-input>
            </div>
        </div>

        <!-- 热搜关键词 -->
        <?php if (count($this->hotKeywords) > 0): ?>
        <div class="be-mb-200">
            <span class="be-c-999 be-mr-100">热搜：</span>
            <?php foreach ($this->hotKeywords as $keyword): ?>
            <el-link type="primary" class="be-mr-100" @click="searchKeyword('<?php echo htmlspecialchars($keyword); ?>')"><?php echo htmlspecialchars($keyword); ?></el-link>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 应用筛选 -->
        <?php if (count($this->apps) > 0): ?>
        <div class="be-mb-200">
            <el-radio-group v-model="appName" @change="doSearch" size="medium">
                <el-radio-button label="">全部</el-radio-button>
                <?php foreach ($this->apps as $app): ?>
                <el-radio-button label="<?php echo $app->name; ?>"><?php echo $app->label; ?></el-radio-button>
                <?php endforeach; ?>
            </el-radio-group>
        </div>
        <?php endif; ?>

        <!-- 搜索结果 -->
        <?php if (!empty($this->result['rows'])): ?>
        <div class="be-mb-100 be-c-999">
            共找到 <?php echo $this->result['total']; ?> 条结果
        </div>

        <?php foreach ($this->result['rows'] as $item): ?>
        <div class="be-bb-eee be-pb-200 be-mb-200 search-result-item">
            <div class="be-row">
                <?php if ($item->image !== ''): ?>
                <div class="be-col-auto be-mr-100">
                    <el-image
                        style="width: 120px; height: 90px"
                        :src="'<?php echo $item->image; ?>'"
                        fit="cover"></el-image>
                </div>
                <?php endif; ?>
                <div class="be-col">
                    <div class="be-mb-50">
                        <a href="<?php echo $item->url; ?>" target="_blank" class="be-fs-110 be-fw-bold search-result-title">
                            <?php echo $item->title; ?>
                        </a>
                    </div>
                    <?php if ($item->summary !== ''): ?>
                    <div class="be-c-666 be-mb-50 be-fs-90">
                        <?php echo mb_substr($item->summary, 0, 200); ?>
                    </div>
                    <?php endif; ?>
                    <div class="be-c-999 be-fs-80">
                        <span class="be-mr-200"><?php echo $item->app_label; ?></span>
                        <?php if ($item->author !== ''): ?>
                        <span class="be-mr-200"><?php echo $item->author; ?></span>
                        <?php endif; ?>
                        <?php if ($item->publish_time): ?>
                        <span><?php echo $item->publish_time; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- 分页 -->
        <div class="be-ta-center be-mt-300">
            <el-pagination
                background
                layout="prev, pager, next"
                :total="<?php echo $this->result['total']; ?>"
                :page-size="<?php echo $this->result['pageSize']; ?>"
                :current-page="<?php echo $this->result['page']; ?>"
                @current-change="changePage">
            </el-pagination>
        </div>

        <?php elseif ($this->keywords !== '') ?>
        <div class="be-ta-center be-p-300 be-c-999">
            未找到相关结果
        </div>
        <?php endif; ?>

    </div>
    <style>
        .search-result-title {
            color: #1a0dab;
            text-decoration: none;
        }
        .search-result-title:hover {
            text-decoration: underline;
        }
        .search-result-item:hover {
            background-color: #fafafa;
        }
    </style>
    <script>
        let vueCenter = new Vue({
            el: '#app',
            data: {
                keywords: '<?php echo addslashes($this->keywords); ?>',
                appName: '<?php echo addslashes($this->appName); ?>',
            },
            methods: {
                doSearch: function () {
                    let url = '<?php echo beUrl("Search.Search.index"); ?>';
                    let params = [];
                    if (this.keywords) {
                        params.push('keywords=' + encodeURIComponent(this.keywords));
                    }
                    if (this.appName) {
                        params.push('app_name=' + encodeURIComponent(this.appName));
                    }
                    if (params.length > 0) {
                        url += '?' + params.join('&');
                    }
                    window.location.href = url;
                },
                searchKeyword: function (keyword) {
                    this.keywords = keyword;
                    this.doSearch();
                },
                changePage: function (page) {
                    let url = '<?php echo beUrl("Search.Search.index"); ?>';
                    let params = [];
                    if (this.keywords) {
                        params.push('keywords=' + encodeURIComponent(this.keywords));
                    }
                    if (this.appName) {
                        params.push('app_name=' + encodeURIComponent(this.appName));
                    }
                    params.push('page=' + page);
                    url += '?' + params.join('&');
                    window.location.href = url;
                }
            },
        });
    </script>
</be-page-content>
